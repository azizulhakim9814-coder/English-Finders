<?php
/**
 * Tool catalog.
 *
 * Single source of truth for categories, attributes and registered tools.
 *
 * Categories use one axis only: skill. Audience ("for teachers") and technology
 * ("uses AI") are attributes instead, because mixing axes makes some tools
 * belong in several categories at once and forces an arbitrary choice each
 * time. A grammar worksheet generator for teachers that uses AI is a Grammar
 * tool with two attributes — one home, three ways to find it.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Catalog;

use EnglishFindersStudy\Contracts\ToolInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ToolCatalog {
	/**
	 * Skill categories, in learning order rather than alphabetical.
	 *
	 * These become URL segments and navigation, so they are deliberately few
	 * and stable. Adding one is a product decision, not a convenience.
	 */
	public const CATEGORIES = array(
		'vocabulary'    => 'Vocabulary',
		'grammar'       => 'Grammar',
		'reading'       => 'Reading',
		'writing'       => 'Writing',
		'spelling'      => 'Spelling',
		'pronunciation' => 'Pronunciation',
	);

	/**
	 * Cross-cutting attributes.
	 *
	 * `ai` exists partly as disclosure: a tool that calls a paid provider
	 * should be identifiable, both to visitors and when reviewing spend.
	 */
	public const ATTRIBUTES = array(
		'teacher' => 'For teachers',
		'ai'      => 'Uses AI',
		'free'    => 'Free',
	);

	/** @var array<string,ToolInterface> */
	private array $tools = array();

	public function register( ToolInterface $tool ): void {
		$id = $tool->id();

		if ( '' === $id || isset( $this->tools[ $id ] ) ) {
			return;
		}

		if ( ! isset( self::CATEGORIES[ $tool->category() ] ) ) {
			/*
			 * A tool with an unrecognised category is dropped rather than
			 * shown uncategorised: it would be invisible in navigation but
			 * still reachable by URL, which is worse than not registering.
			 */
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'EFS: tool "' . $id . '" has unknown category "' . $tool->category() . '"' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return;
		}

		$this->tools[ $id ] = $tool;
	}

	public function get( string $id ): ?ToolInterface {
		$id = sanitize_key( $id );
		if ( ! $this->is_enabled( $id ) ) {
			return null;
		}

		return $this->tools[ $id ] ?? null;
	}

	/**
	 * Every enabled tool.
	 *
	 * @return array<string,ToolInterface>
	 */
	public function all(): array {
		return array_filter(
			$this->tools,
			fn ( ToolInterface $tool ): bool => $this->is_enabled( $tool->id() )
		);
	}

	/**
	 * Every registered tool, enabled or not.
	 *
	 * The admin tool list needs to show a disabled tool too, so it can be
	 * turned back on — `all()` alone could never surface it again.
	 *
	 * @return array<string,ToolInterface>
	 */
	public function all_registered(): array {
		return $this->tools;
	}

	/**
	 * Whether a registered tool is enabled.
	 *
	 * `efs_settings['disabled_tools']` is a *disable*-list rather than an
	 * enable allow-list: a tool is on unless its id is named there, so an
	 * empty list unambiguously means "nothing is disabled" rather than
	 * needing a special case for "not yet configured". That matters here
	 * specifically because the option has shipped seeded as `[]` since this
	 * plugin's first release, before anything ever read it — an allow-list
	 * would have to treat that pre-existing empty value as "unrestricted" by
	 * exception, where a disable-list gets the same safe result for free.
	 */
	public function is_enabled( string $id ): bool {
		$id = sanitize_key( $id );
		if ( '' === $id || ! isset( $this->tools[ $id ] ) ) {
			return false;
		}

		$settings = get_option( 'efs_settings', array() );
		$disabled = is_array( $settings ) ? (array) ( $settings['disabled_tools'] ?? array() ) : array();
		$disabled = array_map( 'sanitize_key', $disabled );

		return ! in_array( $id, $disabled, true );
	}

	/**
	 * Tools in one category.
	 *
	 * @return array<string,ToolInterface>
	 */
	public function by_category( string $category ): array {
		return array_filter(
			$this->all(),
			static fn ( ToolInterface $tool ): bool => $tool->category() === $category
		);
	}

	/**
	 * Tools carrying an attribute.
	 *
	 * This is what makes a teacher view possible without a teacher category:
	 * a new grammar worksheet appears there automatically.
	 *
	 * @return array<string,ToolInterface>
	 */
	public function by_attribute( string $attribute ): array {
		return array_filter(
			$this->all(),
			static fn ( ToolInterface $tool ): bool => in_array( $attribute, $tool->attributes(), true )
		);
	}

	/**
	 * Categories that actually contain something.
	 *
	 * Navigation should not offer an empty category: a section promising
	 * reading tools and delivering none is worse than not listing it yet.
	 *
	 * @return array<string,string>
	 */
	public function active_categories(): array {
		$active = array();

		foreach ( self::CATEGORIES as $slug => $label ) {
			if ( ! empty( $this->by_category( $slug ) ) ) {
				$active[ $slug ] = $label;
			}
		}

		return $active;
	}

	/**
	 * Register hooks for every enabled tool.
	 *
	 * A disabled tool's `register()` is never called, so its shortcode and
	 * AJAX actions are never wired up at all — not merely hidden, unreachable.
	 */
	public function register_all(): void {
		foreach ( $this->all() as $tool ) {
			$tool->register();
		}
	}
}
