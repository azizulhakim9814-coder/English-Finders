<?php
/**
 * Object-cache and transient cache adapter.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Not final: Word Games Pro subclasses this to keep its original class name and
 * namespace while the implementation lives here, so its existing call sites did
 * not all have to change in one release. Marking it final would make that
 * subclass a fatal error on load.
 */
class Cache {
	private const GROUP             = 'word-unscramble-cheats';
	private const GENERATION_OPTION = 'wuc_cache_generation';

	public function get( string $key, ?bool &$hit = null, string $namespace = 'general' ): mixed {
		$physical = $this->physical_key( $key, $namespace );
		$found    = false;
		$value    = wp_cache_get( $physical, self::GROUP, false, $found );
		if ( $found ) {
			$hit = true;
			return $value;
		}

		if ( wp_using_ext_object_cache() ) {
			$hit = false;
			return null;
		}

		$value = get_transient( 'wuc_' . $physical );
		if ( false !== $value ) {
			if ( is_array( $value ) && 1 === (int) ( $value['__wuc_cache'] ?? 0 ) && array_key_exists( 'value', $value ) ) {
				$value = $value['value'];
			}
			$hit = true;
			wp_cache_set( $physical, $value, self::GROUP, $this->ttl() );
			return $value;
		}

		$hit = false;
		return null;
	}

	public function set( string $key, mixed $value, ?int $ttl = null, string $namespace = 'general' ): void {
		$ttl      = max( 1, $ttl ?? $this->ttl() );
		$physical = $this->physical_key( $key, $namespace );
		wp_cache_set( $physical, $value, self::GROUP, $ttl );

		if ( ! wp_using_ext_object_cache() ) {
			set_transient( 'wuc_' . $physical, array( '__wuc_cache' => 1, 'value' => $value ), $ttl );
		}
	}

	public function delete( string $key, string $namespace = 'general' ): void {
		$physical = $this->physical_key( $key, $namespace );
		wp_cache_delete( $physical, self::GROUP );
		delete_transient( 'wuc_' . $physical );
	}

	/**
	 * Invalidate every plugin cache key without requiring cache-group support.
	 *
	 * Stale persistent-object-cache values become unreachable because every
	 * physical key includes the generation number.
	 */
	public function flush(): void {
		global $wpdb;

		$generation = $this->generation() + 1;
		update_option( self::GENERATION_OPTION, $generation, false );

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wuc_%' OR option_name LIKE '_transient_timeout_wuc_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( self::GROUP );
		}
	}

	public function generation(): int {
		$generation = max( 1, absint( get_option( self::GENERATION_OPTION, 1 ) ) );
		if ( false === get_option( self::GENERATION_OPTION, false ) ) {
			add_option( self::GENERATION_OPTION, $generation, '', false );
		}
		return $generation;
	}

	private function physical_key( string $key, string $namespace ): string {
		$namespace = sanitize_key( $namespace );
		$namespace = '' !== $namespace ? $namespace : 'general';
		return sprintf( 'v%d_%s_%s', $this->generation(), $namespace, hash( 'sha256', $key ) );
	}

	private function ttl(): int {
		$settings = get_option( 'wuc_settings', array() );
		return max( 60, absint( $settings['cache_ttl'] ?? HOUR_IN_SECONDS ) );
	}
}
