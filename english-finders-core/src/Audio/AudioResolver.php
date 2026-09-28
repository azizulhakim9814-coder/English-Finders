<?php
/**
 * Audio resolution chain.
 *
 * Level 1  A recorded pronunciation URL that is actually reachable
 * Level 2  Generated speech, stored once and served as a static file
 * Level 3  Browser speech synthesis (handled client-side; not this class)
 *
 * The reachability check at level 1 is the load-bearing part. The Free
 * Dictionary API continues to advertise recorded audio for most words while
 * its media host serves none of it — a documented, long-standing failure. So
 * "the payload contains a URL" is not evidence that audio exists, and a naive
 * chain would never fall through to level 2 because it believes level 1
 * succeeded.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Audio;

use EnglishFindersCore\Contracts\AudioProviderInterface;
use EnglishFindersCore\Database\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AudioResolver {
	/** Reachability verdicts are cached this long. */
	private const REACHABILITY_TTL = WEEK_IN_SECONDS;

	/** Kept short: this runs inside a visitor-facing request. */
	private const HEAD_TIMEOUT = 4;

	public function __construct(
		private readonly AudioStore $store,
		private readonly ?AudioProviderInterface $provider = null
	) {}

	/**
	 * Resolve the best available audio for a word.
	 *
	 * @param string       $word       Headword.
	 * @param list<string> $candidates Recorded-audio URLs from dictionary providers.
	 * @return array{url:string,source:string,label:string}|null Null means fall through to the browser.
	 */
	public function resolve( string $word, array $candidates ): ?array {
		// Level 1 — a recorded URL that actually resolves.
		foreach ( $candidates as $candidate ) {
			$candidate = is_string( $candidate ) ? trim( $candidate ) : '';
			if ( '' === $candidate || ! str_starts_with( $candidate, 'http' ) ) {
				continue;
			}
			if ( $this->is_reachable( $candidate ) ) {
				return array(
					'url'    => $candidate,
					'source' => 'recorded',
					// The caller keeps whatever label the provider supplied.
					'label'  => '',
				);
			}
		}

		// Level 2 — generated speech.
		$generated = $this->generated_url( $word );
		if ( null !== $generated ) {
			return array(
				'url'    => $generated,
				'source' => 'generated',
				'label'  => $this->accent_label(),
			);
		}

		// Level 3 — caller falls back to the browser.
		return null;
	}

	/**
	 * Already-generated audio for a word, without generating anything.
	 *
	 * A read-only file check: no dictionary provider call, no synthesis
	 * request, no generation lock. Exists so a caller that only wants to
	 * know "has this already been resolved" can find out at the cost of one
	 * `is_readable()`/`filesize()` pair, instead of paying for the level 1/2
	 * chain in resolve() -- which, for a word with no *recorded* audio (true
	 * of every purely local dictionary entry), still runs the full remote
	 * provider lookup before it ever reaches the store check that would have
	 * found this same file.
	 *
	 * @return array{url:string,source:string,label:string}|null
	 */
	public function existing( string $word ): ?array {
		$settings = $this->settings();
		if ( empty( $settings['tts_enabled'] ) ) {
			return null;
		}

		$voice    = (string) ( $settings['tts_voice'] ?? 'alloy' );
		$key      = $this->store->key( $word, $voice );
		$existing = $this->store->url( $key );

		return null === $existing
			? null
			: array(
				'url'    => $existing,
				'source' => 'generated',
				'label'  => $this->accent_label(),
			);
	}

	/**
	 * Existing or newly generated audio for a word.
	 *
	 * Checks the store first, so a word is only ever paid for once.
	 */
	private function generated_url( string $word ): ?string {
		$settings = $this->settings();
		if ( empty( $settings['tts_enabled'] ) ) {
			return null;
		}

		$voice = (string) ( $settings['tts_voice'] ?? 'alloy' );
		$key   = $this->store->key( $word, $voice );

		$existing = $this->store->url( $key );
		if ( null !== $existing ) {
			return $existing;
		}

		if ( ! $this->provider instanceof AudioProviderInterface || ! $this->provider->is_configured() ) {
			return null;
		}

		/*
		 * Generation lock.
		 *
		 * Several visitors hitting an ungenerated word at once would otherwise
		 * each trigger a paid call for the same audio. The first request wins;
		 * the rest fall through to the browser for that one play and pick up
		 * the stored file afterwards.
		 */
		$lock = 'efc_tts_lock_' . $key;
		if ( false !== get_transient( $lock ) ) {
			return null;
		}
		set_transient( $lock, 1, MINUTE_IN_SECONDS );

		$result = $this->provider->synthesize( $word, $voice );
		if ( is_wp_error( $result ) ) {
			delete_transient( $lock );
			return null;
		}

		$stored = $this->store->put( $key, $result['body'] );
		delete_transient( $lock );

		return is_wp_error( $stored ) ? null : $stored;
	}

	/**
	 * Whether a URL actually serves something.
	 *
	 * Cached per URL, so the cost is one HEAD request per distinct audio URL
	 * for the life of the cache entry rather than one per page view. A failed
	 * check is cached too — repeatedly re-checking a host that has been broken
	 * for years would add latency to every single lookup.
	 */
	private function is_reachable( string $url ): bool {
		$cache_key = 'efc_audio_ok_' . md5( $url );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return '1' === $cached;
		}

		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => self::HEAD_TIMEOUT,
				'redirection' => 3,
			)
		);

		$ok = false;
		if ( ! is_wp_error( $response ) ) {
			$code   = (int) wp_remote_retrieve_response_code( $response );
			$length = (int) wp_remote_retrieve_header( $response, 'content-length' );
			$type   = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );

			/*
			 * A 200 alone is not enough: a host can return an HTML error page
			 * with a 200. Require an audio content type, or a body large enough
			 * that it cannot be an error page when the type header is absent.
			 */
			$ok = 200 === $code
				&& ( str_contains( $type, 'audio' ) || str_contains( $type, 'octet-stream' ) || $length > 1024 );
		}

		set_transient( $cache_key, $ok ? '1' : '0', self::REACHABILITY_TTL );

		return $ok;
	}

	/**
	 * Human label describing the accent the configured voice produces.
	 *
	 * Only Core knows which voice is in use, so only Core can label the result
	 * accurately. Without this, a button carrying the provider's original "AU"
	 * or "UK" label would play American generated speech — a label that is not
	 * merely inconsistent but wrong.
	 *
	 * Prefix conventions are Kokoro's (af_/am_ American, bf_/bm_ British). Any
	 * other model's voice naming is unknown here, so a neutral label is used
	 * rather than guessing an accent.
	 */
	private function accent_label(): string {
		$settings = $this->settings();
		$voice    = strtolower( trim( (string) ( $settings['tts_voice'] ?? '' ) ) );

		if ( str_starts_with( $voice, 'af_' ) || str_starts_with( $voice, 'am_' ) ) {
			return __( 'US', 'english-finders-core' );
		}
		if ( str_starts_with( $voice, 'bf_' ) || str_starts_with( $voice, 'bm_' ) ) {
			return __( 'UK', 'english-finders-core' );
		}

		return __( 'Pronunciation', 'english-finders-core' );
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}
}
