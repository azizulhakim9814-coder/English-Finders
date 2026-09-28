<?php
/**
 * OpenRouter text-to-speech provider.
 *
 * One connection reaches OpenAI, Google and Mistral speech models, so a single
 * key and a single bill cover every option rather than requiring a separate
 * integration per vendor. Switching model is a settings change; switching to a
 * direct vendor connection later means adding another class implementing
 * AudioProviderInterface, with nothing else changing.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Audio;

use EnglishFindersCore\Contracts\AudioProviderInterface;
use EnglishFindersCore\Database\Installer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenRouterTtsProvider implements AudioProviderInterface {
	private const ENDPOINT = 'https://openrouter.ai/api/v1/audio/speech';

	/** Generous enough for a cold model, short enough not to hold a request open. */
	private const TIMEOUT = 20;

	public function id(): string {
		return 'openrouter';
	}

	public function is_configured(): bool {
		return '' !== $this->api_key();
	}

	/**
	 * @return array{body:string,mime:string}|WP_Error
	 */
	public function synthesize( string $text, string $voice = '' ): array|WP_Error {
		$text = trim( $text );
		if ( '' === $text ) {
			return new WP_Error( 'efc_tts_empty', __( 'Nothing to synthesise.', 'english-finders-core' ) );
		}

		/*
		 * Hard length ceiling.
		 *
		 * This provider exists to speak dictionary headwords. Capping the input
		 * means a malformed or hostile request cannot turn one call into a
		 * large billable generation.
		 *
		 * mbstring is not guaranteed to be present on every host, so the
		 * multibyte-aware count is used only when available. strlen overcounts
		 * multibyte input, which fails safe here: it can reject slightly early,
		 * never allow something oversized through.
		 */
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		if ( $length > 64 ) {
			return new WP_Error( 'efc_tts_too_long', __( 'Text is too long to synthesise.', 'english-finders-core' ) );
		}

		$key = $this->api_key();
		if ( '' === $key ) {
			return new WP_Error( 'efc_tts_unconfigured', __( 'No text-to-speech API key is configured.', 'english-finders-core' ) );
		}

		$settings = $this->settings();
		$model    = (string) ( $settings['tts_model'] ?? 'hexgrad/kokoro-82m' );
		$voice    = '' !== $voice ? $voice : (string) ( $settings['tts_voice'] ?? 'af_bella' );

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'           => $model,
						'input'           => $text,
						'voice'           => $voice,
						'response_format' => 'mp3',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			/*
			 * The provider's own error text is deliberately not surfaced to the
			 * visitor: it can echo request details. It is logged under WP_DEBUG
			 * for diagnosis and the visitor sees a generic failure.
			 */
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EFC TTS HTTP ' . $code . ': ' . substr( $body, 0, 300 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return new WP_Error(
				'efc_tts_http_error',
				__( 'The speech service returned an error.', 'english-finders-core' ),
				array(
					'status' => $code,
					/*
					 * Carried for the admin diagnostic screen only. Surfacing
					 * the provider's own message is what turns "it silently
					 * does nothing" into an actionable error, but it can echo
					 * request details, so it is never shown to visitors.
					 */
					'detail' => substr( wp_strip_all_tags( $body ), 0, 400 ),
					'model'  => $model,
				)
			);
		}

		$mime = (string) wp_remote_retrieve_header( $response, 'content-type' );

		/*
		 * Some gateways return a JSON error body with HTTP 200. Treating that
		 * as audio would write a text file with an .mp3 extension and cache the
		 * failure permanently, so the payload is checked rather than trusted.
		 */
		if ( '' === $body || str_contains( strtolower( $mime ), 'json' ) || str_starts_with( ltrim( $body ), '{' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EFC TTS non-audio 200 response: ' . substr( $body, 0, 300 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return new WP_Error(
				'efc_tts_bad_payload',
				__( 'The speech service returned an unexpected response.', 'english-finders-core' ),
				array(
					'detail' => substr( wp_strip_all_tags( $body ), 0, 400 ),
					'model'  => $model,
				)
			);
		}

		return array(
			'body' => $body,
			'mime' => '' !== $mime ? $mime : 'audio/mpeg',
		);
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Resolve the API key.
	 *
	 * A constant in wp-config.php takes precedence over the stored setting, so
	 * the key can be kept out of the database entirely if preferred.
	 */
	private function api_key(): string {
		if ( defined( 'EFC_OPENROUTER_KEY' ) && is_string( EFC_OPENROUTER_KEY ) ) {
			return trim( EFC_OPENROUTER_KEY );
		}
		$settings = $this->settings();
		return trim( (string) ( $settings['openrouter_key'] ?? '' ) );
	}
}
