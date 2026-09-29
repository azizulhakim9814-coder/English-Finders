<?php
/**
 * My Library section, included from account-shell.php (Phase A3).
 *
 * Rendered only when WGP's Support\Api facade is actually available and
 * new enough (LibraryController::data_for_user() returns null otherwise)
 * -- silently absent rather than a broken or "coming soon" section, the
 * same honesty principle every earlier section of this page has applied.
 *
 * Displays `item_key` rather than parsing the saved payload's own fields
 * (`game`, `query`, etc.) -- that payload's shape is defined by each WGP
 * tool's own client-side JS when it calls the generic save() endpoint, not
 * centrally typed server-side, so it cannot be relied on to always carry
 * the same keys. `item_key` and the timestamps are the only fields the
 * repository's own SELECT always includes.
 *
 * 0.6.0 visual pass: icons per item and an actionable empty state (a link
 * to /games/, not just a sentence) -- closing the gap against Duolingo's
 * "Add friends" empty-state card, which prompts an action rather than only
 * stating a fact.
 *
 * @package EnglishFindersAccount
 *
 * @var array{favorites: list<array<string,mixed>>, history: list<array<string,mixed>>}|null $library
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Icons;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( null === $library ) {
	return;
}

/**
 * Splits a "tool:value" item_key into a readable "Tool: value" label.
 * Falls back to the raw key when it doesn't split cleanly.
 */
$format_item = static function ( string $item_key ): string {
	$parts = explode( ':', $item_key, 2 );
	if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
		return $item_key;
	}

	$tool = ucwords( str_replace( '-', ' ', $parts[0] ) );

	return $tool . ': ' . $parts[1];
};

/** 0.13.0: "22 Sep 2026" instead of the raw database timestamp. */
$format_date = static function ( string $datetime ): string {
	return '' !== $datetime ? (string) mysql2date( 'j M Y', $datetime ) : '';
};
?>
<section class="efa-account-section efa-library-section" id="efa-section-library">
	<h2><?php esc_html_e( 'My library', 'english-finders-account' ); ?></h2>

	<h3><?php esc_html_e( 'Saved words', 'english-finders-account' ); ?></h3>
	<?php if ( array() === $library['favorites'] ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'bookmark', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons::svg() only ever returns this class's own fixed, trusted SVG markup, never user input. ?>
			<p class="description"><?php esc_html_e( 'No saved words yet. In the word tools, tap the heart next to any word to save it here.', 'english-finders-account' ); ?></p>
			<a class="efa-empty-state__cta" href="<?php echo esc_url( Urls::word_unscrambler() ); ?>"><?php esc_html_e( 'Find words to save', 'english-finders-account' ); ?></a>
		</div>
	<?php else : ?>
		<ul class="efa-library-list">
			<?php foreach ( $library['favorites'] as $item ) : ?>
				<li>
					<?php echo Icons::svg( 'bookmark', 'efa-library-item__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php $efa_word_url = Urls::word_page( (string) $item['item_key'] ); ?>
					<?php if ( '' !== $efa_word_url ) : ?>
						<a class="efa-library-item" href="<?php echo esc_url( $efa_word_url ); ?>"><?php echo esc_html( (string) $item['item_key'] ); ?></a>
					<?php else : ?>
						<span class="efa-library-item"><?php echo esc_html( $format_item( (string) $item['item_key'] ) ); ?></span>
					<?php endif; ?>
					<span class="efa-library-date"><?php echo esc_html( $format_date( (string) $item['updated_at'] ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Recent searches', 'english-finders-account' ); ?></h3>
	<?php if ( array() === $library['history'] ) : ?>
		<div class="efa-empty-state">
			<?php echo Icons::svg( 'clock', 'efa-empty-state__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="description"><?php esc_html_e( 'No recent searches yet. Searches you make in the word tools, such as the unscrambler, show up here.', 'english-finders-account' ); ?></p>
			<a class="efa-empty-state__cta" href="<?php echo esc_url( Urls::word_tools() ); ?>"><?php esc_html_e( 'Open word tools', 'english-finders-account' ); ?></a>
		</div>
	<?php else : ?>
		<ul class="efa-library-list">
			<?php foreach ( $library['history'] as $item ) : ?>
				<li>
					<?php echo Icons::svg( 'clock', 'efa-library-item__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="efa-library-item"><?php echo esc_html( $format_item( (string) $item['item_key'] ) ); ?></span>
					<span class="efa-library-date"><?php echo esc_html( $format_date( (string) $item['updated_at'] ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( isset( $mistakes ) && null !== $mistakes ) : // 0.13.0: the notebook shipped in 0.8.0; point to it instead of saying it's coming. ?>
		<p class="description">
			<?php esc_html_e( 'Answers you get wrong in practice are kept separately, in', 'english-finders-account' ); ?>
			<a href="#efa-section-mistakes"><?php esc_html_e( 'your mistake notebook', 'english-finders-account' ); ?></a>.
		</p>
	<?php endif; ?>
</section>
