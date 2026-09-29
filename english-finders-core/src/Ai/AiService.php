<?php
/**
 * The `ai` service: the one entry point for paid text generation.
 *
 * English Finders Study (and anything later) asks this service, never the
 * HTTP client, so the on/off switch, the daily limits and the usage log apply
 * to every AI feature in one place:
 *
 *   $ai = Api::service( 'ai' );
 *   if ( $ai->is_enabled() && '' === $ai->quota()->reserve( $user_id ) ) {
 *       $result = $ai->complete_json( $system, $user_text );
 *       if ( is_wp_error( $result ) ) { $ai->quota()->refund( $user_id ); }
 *   }
 *
 * Off by default (`ai_enabled`), for the same reason speech generation is: an
 * unattended install must never start making paid calls on its own.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Ai;

use EnglishFindersCore\Database\Installer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AiService {
	private const USAGE_OPTION = 'efc_ai_usage';

	/** Days of usage history kept for the settings screen. */
	private const USAGE_DAYS = 60;

	public function __construct(
		private readonly OpenRouterTextClient $client,
		private readonly AiQuota $quota
	) {}

	/** Switched on in settings, and a key is available. */
	public function is_enabled(): bool {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );

		return is_array( $settings ) && ! empty( $settings['ai_enabled'] ) && $this->client->is_configured();
	}

	public function quota(): AiQuota {
		return $this->quota;
	}

	public function model(): string {
		return $this->client->model();
	}

	/**
	 * Ask for a JSON object and return it decoded.
	 *
	 * The model is told to answer with JSON only; this still tolerates a code
	 * fence or a sentence around the object, because a model occasionally adds
	 * one and failing the learner's request over it would waste their check.
	 *
	 * @param string $system     Instructions.
	 * @param string $user       The content to work on.
	 * @param int    $max_tokens Output cap.
	 * @return array<string,mixed>|WP_Error
	 */
	public function complete_json( string $system, string $user, int $max_tokens = 1200 ): array|WP_Error {
		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'efc_ai_disabled', __( 'AI features are switched off.', 'english-finders-core' ) );
		}

		$result = $this->client->complete(
			array(
				array(
					'role'    => 'system',
					'content' => $system,
				),
				array(
					'role'    => 'user',
					'content' => $user,
				),
			),
			$max_tokens
		);

		if ( is_wp_error( $result ) ) {
			$this->log( 0, 0, false );
			return $result;
		}

		$this->log( $result['prompt_tokens'], $result['completion_tokens'], true );

		$decoded = self::extract_json( $result['content'] );
		if ( null === $decoded ) {
			return new WP_Error( 'efc_ai_bad_json', __( 'The AI answer could not be read.', 'english-finders-core' ) );
		}

		return $decoded;
	}

	/**
	 * The first JSON object in a string, or null.
	 *
	 * @param string $text Model output.
	 * @return array<string,mixed>|null
	 */
	public static function extract_json( string $text ): ?array {
		$text = trim( $text );

		$decoded = json_decode( $text, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return null;
		}

		$decoded = json_decode( substr( $text, $start, $end - $start + 1 ), true );

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Daily usage history, newest first.
	 *
	 * @return array<string,array{requests:int,failed:int,prompt_tokens:int,completion_tokens:int}>
	 */
	public function usage(): array {
		$log = get_option( self::USAGE_OPTION, array() );
		if ( ! is_array( $log ) ) {
			return array();
		}

		krsort( $log );

		return $log;
	}

	private function log( int $prompt_tokens, int $completion_tokens, bool $ok ): void {
		$log = get_option( self::USAGE_OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		$day = wp_date( 'Y-m-d' );

		$row = $log[ $day ] ?? array(
			'requests'          => 0,
			'failed'            => 0,
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
		);

		++$row['requests'];
		if ( ! $ok ) {
			++$row['failed'];
		}
		$row['prompt_tokens']     += $prompt_tokens;
		$row['completion_tokens'] += $completion_tokens;
		$log[ $day ]               = $row;

		krsort( $log );
		update_option( self::USAGE_OPTION, array_slice( $log, 0, self::USAGE_DAYS, true ), false );
	}
}
