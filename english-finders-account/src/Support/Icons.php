<?php
/**
 * A tiny fixed set of inline SVG icons for the My Account page (0.6.0's
 * visual pass).
 *
 * Hand-authored, generic geometric glyphs (flame, bolt, shield, etc.) --
 * deliberately not pulled from an icon library, so this plugin adds no new
 * dependency for what is a handful of small shapes. `svg()` returns raw
 * markup (not escaped by the caller) since the source is this fixed,
 * trusted array, never user input -- the same trust boundary this project
 * already applies to its own translated strings.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Icons {
	/** @var array<string,string> Path/shape data only -- svg() wraps it in the common <svg> shell. */
	private const PATHS = array(
		'flame'    => '<path d="M12 2c1 3-3 4-3 8a3 3 0 0 0 6 0c0-1-.5-2-1-2.5.5 2 0 3.5-1.5 4.5-1-1-1.5-2-1-3.5-1.5 1-2 2.5-2 4a4.5 4.5 0 0 0 9 0c0-5-4-6-6.5-10.5Z"/>',
		'bolt'     => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"/>',
		'shield'   => '<path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
		'trophy'   => '<path d="M8 4h8v4a4 4 0 0 1-8 0V4Z"/><path d="M8 5H5a3 3 0 0 0 3 4"/><path d="M16 5h3a3 3 0 0 1-3 4"/><path d="M10 15h4v3h-4z"/><path d="M8 21h8"/><path d="M10 18h4"/>',
		'bookmark' => '<path d="M6 3h12v18l-6-4-6 4V3Z"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'lock'     => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
		'award'    => '<circle cx="12" cy="9" r="6"/><path d="m9 14.5-1.5 7L12 19l4.5 2.5-1.5-7"/><path d="m10 9 1.5 1.5L14.5 7.5"/>',
		'chart'    =>'<path d="M4 20h16"/><rect x="5" y="13" width="3" height="5" rx="1"/><rect x="10.5" y="9" width="3" height="9" rx="1"/><rect x="16" y="5" width="3" height="13" rx="1"/>',
	);

	/**
	 * @param string $name  One of the keys in self::PATHS. Falls back to an empty string for an unknown name, rather than a broken/malformed <svg> -- callers are this plugin's own templates, so an unknown name is a coding mistake to notice, not something to paper over with a placeholder glyph.
	 * @param string $class Optional extra CSS class, appended to the base "efa-icon" class.
	 */
	public static function svg( string $name, string $class = '' ): string {
		if ( ! isset( self::PATHS[ $name ] ) ) {
			return '';
		}

		$classes = 'efa-icon' . ( '' !== $class ? ' ' . $class : '' );

		return '<svg class="' . $classes . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . self::PATHS[ $name ] . '</svg>';
	}
}
