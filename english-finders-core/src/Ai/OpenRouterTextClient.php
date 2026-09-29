<?php
/**
 * OpenRouter chat-completion client.
 *
 * The text counterpart of Audio\OpenRouterTtsProvider: the same key, the same
 * single bill, and a model that is a settings value rather than code. Callers
 * never use this class directly -- they go through the `ai` service
 * (Ai\AiService), which owns the on/off switch and the usage limits. Keeping
 * the HTTP detail here means a later move to a direct vendor connection is one
 * new class, with nothing upstream changing.
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

final class OpenRouterTextClient {
	private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

	/** Feedback on a few hundred words takes a while on a cold model; a web request should not wait longer. */
	private const TIMEOUT = 45;

	public const DEFAULT_MODEL = 'anthropic/claude-haiku-4.5';

	/** Hard ceiling on output, whatever a caller asks for, so one request can never turn into a large bill. */
	private const MAX_TOKENS_CEILING = 2000;

	public function is_configured(): bool {
		return '' !== $this->api_key();
	}

	public function model(): string {
		$model = trim( (string) ( $this->settings()['ai_model'] ?? '' ) );

		return '' !== $model ? $model : self::DEFAULT_MODEL;
	}

	/**
	 * Run one chat completion.
	 *
	 * @param list<array{role:string,content:string}> $messages   Conversation, system message first.
	 * @param int                                     $max_tokens Output cap (clamped to the ceiling).
	 * @return array{content:string,model:string,prompt_tokens:int,completion_tokens:int}|WP_Error
	 */
	public function complete( array $messages, int $max_tokens = 1200 ): array|WP_Error {
		$key = $this->api_key();
		if ( '' === $key ) {
			return new WP_Error( 'efc_ai_unconfigured', __( 'No AI API key is configured.', 'english-finders-core' ) );
		}

		$body = array(
			'model'       => $this->model(),
			'messages'    => $messages,
			'max_tokens'  => max( 64, min( self::MAX_TOKENS_CEILING, $max_tokens ) ),
			'temperature' => 0.2,
		);

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => home_url( '/' ),
					'X-Title'       => 'English Finders',
				),
				'body'    => (string) wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'efc_ai_http', $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || ! is_array( $data ) ) {
			$detail = is_array( $data ) ? (string) ( $data['error']['message'] ?? '' ) : '';

			return new WP_Error(
				'efc_ai_status',
				__( 'The AI provider returned an error.', 'english-finders-core' ),
				array(
					'status' => $status,
					'detail' => substr( $detail, 0, 500 ),
				)
			);
		}

		$content = (string) ( $data['choices'][0]['message']['content'] ?? '' );
		if ( '' === trim( $content ) ) {
			return new WP_Error( 'efc_ai_empty', __( 'The AI provider returned an empty answer.', 'english-finders-core' ) );
		}

		return array(
			'content'           => $content,
			'model'             => (string) ( $data['model'] ?? $body['model'] ),
			'prompt_tokens'     => (int) ( $data['usage']['prompt_tokens'] ?? 0 ),
			'completion_tokens' => (int) ( $data['usage']['completion_tokens'] ?? 0 ),
		);
	}

	/** Same precedence as the speech provider: a wp-config.php constant beats the stored setting. */
	private function api_key(): string {
		if ( defined( 'EFC_OPENROUTER_KEY' ) && is_string( EFC_OPENROUTER_KEY ) && '' !== trim( EFC_OPENROUTER_KEY ) ) {
			return trim( EFC_OPENROUTER_KEY );
		}

		return trim( (string) ( $this->settings()['openrouter_key'] ?? '' ) );
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( Installer::SETTINGS_OPTION, array() );

		return is_array( $settings ) ? $settings : array();
	}
}
