<?php
/**
 * AiService::extract_json() and AiQuota assertions (1.16.0).
 *
 * Standalone, no WordPress needed: php tests/test_ai.php
 *
 * @package EnglishFindersCore
 */

namespace EnglishFindersCore\Database {
	define( 'ABSPATH', __DIR__ . '/' );

	final class Installer {
		public const SETTINGS_OPTION = 'efc_settings';
	}
}

namespace {
	// Minimal WordPress stand-ins backed by arrays.
	$GLOBALS['efc_test_options'] = array();
	$GLOBALS['efc_test_meta']    = array();
	$GLOBALS['efc_test_day']     = '2026-09-29';

	function get_option( $name, $fallback = false ) {
		return $GLOBALS['efc_test_options'][ $name ] ?? $fallback;
	}
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['efc_test_options'][ $name ] = $value;
		return true;
	}
	function get_user_meta( $user_id, $key, $single = false ) {
		return $GLOBALS['efc_test_meta'][ $user_id ][ $key ] ?? '';
	}
	function update_user_meta( $user_id, $key, $value ) {
		$GLOBALS['efc_test_meta'][ $user_id ][ $key ] = $value;
		return true;
	}
	function wp_date( $format ) {
		return $GLOBALS['efc_test_day'];
	}

	require dirname( __DIR__ ) . '/src/Ai/OpenRouterTextClient.php';
	require dirname( __DIR__ ) . '/src/Ai/AiQuota.php';
	require dirname( __DIR__ ) . '/src/Ai/AiService.php';

	use EnglishFindersCore\Ai\AiQuota;
	use EnglishFindersCore\Ai\AiService;

	$efc_count = 0;
	$efc_ok    = static function ( bool $cond, string $message ) use ( &$efc_count ): void {
		if ( ! $cond ) {
			fwrite( STDERR, "FAIL: {$message}\n" );
			exit( 1 );
		}
		++$efc_count;
	};

	// extract_json().
	$efc_ok( array( 'a' => 1 ) === AiService::extract_json( '{"a":1}' ), 'plain JSON' );
	$efc_ok( array( 'a' => 1 ) === AiService::extract_json( "```json\n{\"a\":1}\n```" ), 'code fence' );
	$efc_ok( array( 'a' => array( 'b' => 2 ) ) === AiService::extract_json( 'Here you go: {"a":{"b":2}} Thanks!' ), 'surrounding text' );
	$efc_ok( null === AiService::extract_json( 'no json here' ), 'no object' );
	$efc_ok( null === AiService::extract_json( '{broken' ), 'unterminated' );
	$efc_ok( null === AiService::extract_json( '' ), 'empty' );

	// AiQuota with defaults: user 1 free, user 2 Pro.
	$quota = new AiQuota( static fn ( int $id ): bool => 2 === $id );

	$efc_ok( 0 === $quota->daily_limit( 0 ), 'guests have no allowance' );
	$efc_ok( AiQuota::DEFAULT_FREE_DAILY === $quota->daily_limit( 1 ), 'free default' );
	$efc_ok( AiQuota::DEFAULT_PRO_DAILY === $quota->daily_limit( 2 ), 'Pro default' );
	$efc_ok( 'user' === $quota->reserve( 0 ), 'guest cannot reserve' );

	for ( $i = 0; $i < AiQuota::DEFAULT_FREE_DAILY; $i++ ) {
		$efc_ok( '' === $quota->reserve( 1 ), 'free reserve ' . $i );
	}
	$efc_ok( 0 === $quota->remaining( 1 ), 'free allowance used' );
	$efc_ok( 'user' === $quota->reserve( 1 ), 'free over limit refused' );

	$quota->refund( 1 );
	$efc_ok( 1 === $quota->remaining( 1 ), 'refund gives one back' );
	$efc_ok( 2 === $quota->site_used_today(), 'refund also lowers the site count' );

	$GLOBALS['efc_test_day'] = '2026-09-30';
	$efc_ok( AiQuota::DEFAULT_FREE_DAILY === $quota->remaining( 1 ), 'new day resets the user' );
	$efc_ok( 0 === $quota->site_used_today(), 'new day resets the site' );

	// Settings override the defaults, and the site cap applies to everyone.
	$GLOBALS['efc_test_options']['efc_settings'] = array(
		'ai_free_daily'     => 5,
		'ai_pro_daily'      => 50,
		'ai_site_daily_cap' => 2,
	);
	$efc_ok( 5 === $quota->daily_limit( 1 ) && 50 === $quota->daily_limit( 2 ), 'limits from settings' );
	$efc_ok( 5 === $quota->daily_limit_for( false ) && 50 === $quota->daily_limit_for( true ), 'limits by plan' );
	$efc_ok( '' === $quota->reserve( 2 ) && '' === $quota->reserve( 1 ), 'two under the cap' );
	$efc_ok( 'site' === $quota->reserve( 2 ), 'site cap refuses even Pro' );

	$quota->refund( 1 );
	$quota->refund( 1 );
	$efc_ok( 0 === $quota->used_today( 1 ), 'refund never goes negative' );

	echo "{$efc_count} assertions OK\n";
}
