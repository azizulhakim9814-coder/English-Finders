<?php
/**
 * Lightweight PSR-4 autoloader.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {
	private const PREFIX = 'EnglishFindersStudy\\';

	public static function register(): void {
		spl_autoload_register( array( self::class, 'autoload' ) );
	}

	private static function autoload( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$file     = EFS_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
