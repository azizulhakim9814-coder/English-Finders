<?php
/**
 * PracticeBox level-link assertions (1.17.0).
 *
 * Standalone, no WordPress needed: php tests/test_practice_box.php
 *
 * @package EnglishFindersStudy
 */

define( 'ABSPATH', __DIR__ . '/' );

// Minimal stand-ins for the WordPress functions the renderers use.
function __( $s ) { return $s; }
function esc_html__( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr__( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return (string) $s; }

require dirname( __DIR__ ) . '/src/Content/PracticeBox.php';

use EnglishFindersStudy\Content\PracticeBox as B;

$count = 0;
$ok    = static function ( bool $cond, string $message ) use ( &$count ): void {
	if ( ! $cond ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	++$count;
};

$ok( 'B1' === B::level_for( array( 'grammar', 'b1-english-intermediate' ) ), 'B1 category gives B1' );
$ok( 'A2' === B::level_for( array( 'c1-english-advanced', 'a2-english-elementary' ) ), 'lowest level wins' );
$ok( '' === B::level_for( array( 'grammar', 'tenses' ) ), 'no level category gives no level' );
$ok( '' === B::level_for( array() ), 'no categories' );
$ok( 'C2' === B::level_for( array( 'c2-english-proficiency' ) ), 'C2 category' );
$ok( 6 === count( B::LEVEL_CATEGORIES ) && 6 === count( B::LEVEL_NAMES ), 'six levels' );
foreach ( B::LEVEL_CATEGORIES as $level ) {
	$ok( isset( B::LEVEL_NAMES[ $level ] ), "name for {$level}" );
}
// The existing tool rules are unchanged.
$ok( 'error-correction' === B::tool_for( array( 'grammar', 'c1-english-advanced' ) ), 'upper-level grammar still gets Error Correction' );
$ok( 'grammar-quiz' === B::tool_for( array( 'grammar', 'a1-english-beginner' ) ), 'lower-level grammar still gets Grammar Quiz' );

$level = array( 'level' => 'B1', 'name' => 'B1 Intermediate', 'hub' => 'https://e.com/learn/b1/', 'course' => 'https://e.com/courses/b1/' );
$r     = new ReflectionClass( B::class );
$full  = $r->getMethod( 'render' );
$full->setAccessible( true );
$only  = $r->getMethod( 'render_level_only' );
$only->setAccessible( true );

$with    = $full->invoke( null, 'grammar-quiz', 'https://e.com/grammar-quiz/', 'https://e.com/english-level-test/', $level );
$without = $full->invoke( null, 'grammar-quiz', 'https://e.com/grammar-quiz/', 'https://e.com/english-level-test/', array() );
$compact = $only->invoke( null, $level );

$ok( 1 === substr_count( $with, 'id="efs-practice-box-css"' ), 'style printed once' );
$ok( 0 === substr_count( $without . $compact, 'id="efs-practice-box-css"' ), 'style not repeated' );
$ok( str_contains( $with, 'Written for B1 Intermediate learners.' ) && str_contains( $with, 'https://e.com/learn/b1/' ) && str_contains( $with, 'https://e.com/courses/b1/' ), 'level row with hub + course' );
$ok( ! str_contains( $with, 'Not sure of your level?' ), 'levelled post: no generic level-test line' );
$ok( str_contains( $without, 'Not sure of your level?' ) && ! str_contains( $without, 'Written for' ), 'unlevelled post unchanged' );
$ok( str_contains( $compact, 'efs-practice-box--level' ) && str_contains( $compact, '/learn/b1/' ) && ! str_contains( $compact, 'efs-practice-box__btn' ), 'compact level-only box' );
$no_course = $only->invoke( null, array( 'course' => '' ) + $level );
$ok( ! str_contains( $no_course, 'Free B1 course' ) && str_contains( $no_course, '/learn/b1/' ), 'no course link when course missing' );

foreach ( array( $with, $without, $compact ) as $html ) {
	libxml_use_internal_errors( true );
	$d = new DOMDocument();
	$d->loadHTML( '<!doctype html><html><body>' . $html . '</body></html>' );
	$errors = array_filter( libxml_get_errors(), static fn ( $e ) => $e->level >= LIBXML_ERR_ERROR && ! str_contains( $e->message, 'Tag aside' ) );
	libxml_clear_errors();
	$ok( array() === $errors, 'well-formed HTML' );
}

echo "{$count} assertions OK\n";
