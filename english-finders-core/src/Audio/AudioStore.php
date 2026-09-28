<?php
/**
 * Generated-audio file store.
 *
 * Audio is generated once per word and written to the uploads directory, then
 * served as a static file. Repeat plays never reach PHP, never reach the
 * provider, and cost nothing — which is the whole point of generate-once.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Audio;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AudioStore {
	private const SUBDIR = 'english-finders-audio';

	/**
	 * Storage key for a word/voice pair.
	 *
	 * Includes the voice so changing voice produces a new file rather than
	 * silently serving the previous voice forever. Hashed so the filename is
	 * always filesystem-safe regardless of the input.
	 */
	public function key( string $word, string $voice ): string {
		return substr( hash( 'sha256', strtolower( trim( $word ) ) . '|' . $voice ), 0, 40 );
	}

	/** Absolute path for a key. Null when the uploads directory is unusable. */
	public function path( string $key ): ?string {
		$dir = $this->dir();
		return null === $dir ? null : $dir . '/' . $key . '.mp3';
	}

	/** Public URL for a key, or null when it has not been generated yet. */
	public function url( string $key ): ?string {
		$path = $this->path( $key );
		if ( null === $path || ! is_readable( $path ) || filesize( $path ) < 512 ) {
			/*
			 * The size floor guards against a truncated or empty write being
			 * treated as a valid cached file. A real word is several KB; a few
			 * hundred bytes means something went wrong mid-write.
			 */
			return null;
		}

		$uploads = wp_get_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		return $uploads['baseurl'] . '/' . self::SUBDIR . '/' . $key . '.mp3';
	}

	/**
	 * Write audio for a key.
	 *
	 * Writes to a temporary file first and renames into place, so a failure
	 * part-way through cannot leave a half-written file that later reads as a
	 * valid cache hit.
	 *
	 * @return string|WP_Error Public URL on success.
	 */
	public function put( string $key, string $body ): string|WP_Error {
		$path = $this->path( $key );
		if ( null === $path ) {
			return new WP_Error( 'efc_audio_dir', __( 'The audio directory could not be created.', 'english-finders-core' ) );
		}

		$temp = $path . '.' . wp_generate_password( 8, false ) . '.tmp';
		if ( false === file_put_contents( $temp, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new WP_Error( 'efc_audio_write', __( 'The audio file could not be written.', 'english-finders-core' ) );
		}

		if ( ! rename( $temp, $path ) ) {
			@unlink( $temp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'efc_audio_write', __( 'The audio file could not be finalised.', 'english-finders-core' ) );
		}

		$url = $this->url( $key );
		return null === $url
			? new WP_Error( 'efc_audio_verify', __( 'The audio file was written but could not be verified.', 'english-finders-core' ) )
			: $url;
	}

	/** Total bytes currently stored, for the admin screen. */
	public function usage_bytes(): int {
		$dir = $this->dir();
		if ( null === $dir ) {
			return 0;
		}
		$total = 0;
		foreach ( glob( $dir . '/*.mp3' ) ?: array() as $file ) {
			$total += (int) filesize( $file );
		}
		return $total;
	}

	public function count(): int {
		$dir = $this->dir();
		return null === $dir ? 0 : count( glob( $dir . '/*.mp3' ) ?: array() );
	}

	/** Remove every generated file. Used when changing voice or clearing cache. */
	public function purge(): int {
		$dir = $this->dir();
		if ( null === $dir ) {
			return 0;
		}
		$removed = 0;
		foreach ( glob( $dir . '/*.mp3' ) ?: array() as $file ) {
			if ( unlink( $file ) ) {
				++$removed;
			}
		}
		return $removed;
	}

	/** Ensure the directory exists. Null when uploads are unavailable. */
	private function dir(): ?string {
		$uploads = wp_get_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		$dir = $uploads['basedir'] . '/' . self::SUBDIR;
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return null;
		}

		return $dir;
	}
}
