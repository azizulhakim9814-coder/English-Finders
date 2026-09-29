<?php
/**
 * UsageRepository::normalize() assertions (1.15.0).
 *
 * Standalone, no WordPress needed: php tests/test_usage.php
 *
 * @package EnglishFindersCore
 */

namespace EnglishFindersCore\Database {
	define( 'ABSPATH', __DIR__ . '/' );

	/** Minimal stand-in: normalize() never touches the database. */
	final class Schema {
		public static function table( string $name ): string {
			return 'wp_efc_' . $name;
		}
	}
}

namespace {
	require dirname( __DIR__ ) . '/src/Usage/UsageRepository.php';

	use EnglishFindersCore\Usage\UsageRepository as U;

	$efc_count = 0;
	$efc_ok    = static function ( bool $cond, string $message ) use ( &$efc_count ): void {
		if ( ! $cond ) {
			fwrite( STDERR, "FAIL: {$message}\n" );
			exit( 1 );
		}
		++$efc_count;
	};

	$efc_ok(
		U::normalize( array( 'efs-grammar-quiz:correct' => 3, 'wgp-daily-unscramble:solved' => 1 ) ) == array(
			'efs-grammar-quiz'     => array( 'correct' => 3, 'visit' => 1 ),
			'wgp-daily-unscramble' => array( 'solved' => 1, 'visit' => 1 ),
		),
		'counts plus one visit per source'
	);
	$efc_ok( array() === U::normalize( array( 'evil:correct' => 1 ) ), 'unknown prefix dropped' );
	$efc_ok( array() === U::normalize( array( 'efs-x:visit' => 5 ) ), 'browser cannot report visits' );
	$efc_ok( array() === U::normalize( array( 'efs-x:correct' => 0 ) ), 'zero dropped' );
	$efc_ok( array() === U::normalize( array( 'efs-x:correct' => -4 ) ), 'negative dropped' );
	$efc_ok( array() === U::normalize( array( 'efs-x:correct' => 'abc' ) ), 'non-numeric dropped' );
	$efc_ok( 500 === U::normalize( array( 'efs-x:correct' => 99999 ) )['efs-x']['correct'], 'count capped at 500' );
	$efc_ok( array() === U::normalize( array( 'efs-X:correct' => 1 ) ), 'uppercase source dropped' );
	$efc_ok( array() === U::normalize( array( 'efs-x:correct:y' => 1 ) ), 'extra colon dropped' );
	$efc_ok( array() === U::normalize( array( 'efs-' . str_repeat( 'a', 41 ) . ':correct' => 1 ) ), 'over-long source dropped' );
	$efc_ok( array() === U::normalize( 'str' ) && array() === U::normalize( null ), 'non-array input' );

	$efc_many = array();
	for ( $i = 0; $i < 50; $i++ ) {
		$efc_many[ "efs-t{$i}:correct" ] = 1;
	}
	$efc_ok( 30 === count( U::normalize( $efc_many ) ), 'at most 30 pairs per report' );
	$efc_ok(
		U::normalize( array( 'efs-a:correct' => 2, 'efs-a:finished' => 1 ) ) == array( 'efs-a' => array( 'correct' => 2, 'visit' => 1, 'finished' => 1 ) ),
		'one visit per source, however many kinds'
	);

	echo "{$efc_count} assertions OK\n";
}
