<?php
/**
 * Text-to-speech provider contract.
 *
 * Deliberately separate from DictionaryProviderInterface. That contract
 * *looks up* an existing entry and returns definitions, with audio arriving
 * incidentally as URLs inside the payload. A TTS provider *generates* audio
 * from text. Forcing both through one interface would mean a lookup method
 * that returns nothing useful for TTS and a synth method meaningless for
 * dictionaries.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface AudioProviderInterface {
	/** Stable identifier, e.g. "openrouter". */
	public function id(): string;

	/** Whether the provider is configured well enough to be attempted. */
	public function is_configured(): bool;

	/**
	 * Synthesise speech for a word.
	 *
	 * Returns raw binary audio, never a URL: storage and serving are the
	 * caller's concern, so a provider cannot impose its own caching policy.
	 *
	 * @param string $text  Word or phrase to speak.
	 * @param string $voice Provider-specific voice identifier.
	 * @return array{body:string,mime:string}|\WP_Error
	 */
	public function synthesize( string $text, string $voice = '' ): array|\WP_Error;
}
