<?php
/**
 * WritingFeedback + WritingPromptBank assertions (1.18.0).
 *
 * Standalone, no WordPress needed: php tests/test_writing_feedback.php
 *
 * @package EnglishFindersStudy
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

// Minimal stand-ins for the WordPress functions the tested code uses.
function __( $s ) { return $s; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }

require dirname( __DIR__ ) . '/src/Contracts/ToolInterface.php';
require dirname( __DIR__ ) . '/src/Tools/WritingPromptBank.php';
require dirname( __DIR__ ) . '/src/Tools/WritingFeedback.php';

use EnglishFindersStudy\Tools\WritingFeedback as W;
use EnglishFindersStudy\Tools\WritingPromptBank as Bank;

$count = 0;
$ok    = static function ( bool $cond, string $message ) use ( &$count ): void {
	if ( ! $cond ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	++$count;
};

// Task bank.
$all = Bank::all();
$ok( count( $all ) >= 30, 'at least 30 tasks' );
$browser = Bank::for_browser();
foreach ( Bank::LEVELS as $level ) {
	$ok( isset( $browser[ $level ] ) && count( $browser[ $level ] ) >= 5, "5+ tasks at {$level}" );
	$ok( null !== Bank::get( strtolower( $level ) . '-free' ), "free topic at {$level}" );
	$range = Bank::range( $level );
	$ok( $range[0] < $range[1], "range at {$level}" );
	list( $min, $max ) = W::word_limits( $level );
	$ok( $min >= 10 && $min <= $range[0] && $max >= $range[1] && $max <= 400, "limits at {$level}" );
}
foreach ( $all as $id => $task ) {
	$ok( 1 === preg_match( '/^[a-z0-9-]+$/', $id ) && str_starts_with( $id, strtolower( $task['level'] ) . '-' ), "id {$id} matches its level" );
	$ok( '' !== $task['title'] && '' !== $task['task'], "task {$id} has text" );
}
$ok( null === Bank::get( 'nope' ), 'unknown task' );

// Word counting.
$ok( 0 === W::word_count( '' ), 'empty text' );
$ok( 5 === W::word_count( "I don't like well-known  films." ), 'contractions and hyphens count once' );
$ok( 3 === W::word_count( "Café, naïve — résumé!" ), 'accented words' );
$ok( 3 === W::word_count( 'I have 3' ), 'digits count' );
$ok( "Line one.\n\nLine two." === W::clean_text( "  Line   one.\r\n\r\n\r\n\r\nLine two.  " ), 'clean_text normalises' );

// Shaping the model answer.
$good = W::shape(
	array(
		'cefr_estimate'    => 'b1',
		'summary'          => ' A <b>clear</b> text. ',
		'strengths'        => array( 'Good linking.', '', 'Nice vocabulary.', 'Clear ending.', 'Extra one.' ),
		'corrections'      => array(
			array( 'original' => 'I go yesterday', 'corrected' => 'I went yesterday', 'explanation' => 'Past simple.' ),
			array( 'original' => 'same', 'corrected' => 'same', 'explanation' => 'No change, dropped.' ),
			array( 'original' => '', 'corrected' => 'x', 'explanation' => 'Empty, dropped.' ),
			'not an array',
		),
		'task_fit'         => 'Answers the task.',
		'improved_version' => "Para one.\n\n\n\nPara two.",
		'next_step'        => array( 'not scalar' ),
	)
);
$ok( is_array( $good ), 'valid answer shaped' );
$ok( 'B1' === $good['cefr_estimate'], 'estimate upper-cased' );
$ok( 'A clear text.' === $good['summary'], 'tags stripped, trimmed' );
$ok( 3 === count( $good['strengths'] ) && 'Clear ending.' === $good['strengths'][2], 'strengths capped at 3, blanks dropped' );
$ok( 1 === count( $good['corrections'] ) && 'I went yesterday' === $good['corrections'][0]['corrected'], 'bad corrections dropped' );
$ok( "Para one.\n\nPara two." === $good['improved_version'], 'improved version keeps paragraphs' );
$ok( '' === $good['next_step'], 'non-scalar becomes empty' );

$many = array();
for ( $i = 0; $i < 12; $i++ ) {
	$many[] = array( 'original' => "a{$i}", 'corrected' => "b{$i}", 'explanation' => '' );
}
$capped = W::shape( array( 'summary' => 'ok', 'corrections' => $many, 'cefr_estimate' => 'Z9' ) );
$ok( 8 === count( $capped['corrections'] ), 'corrections capped at 8' );
$ok( '' === $capped['cefr_estimate'], 'unknown level dropped' );
$ok( 600 === mb_strlen( W::shape( array( 'summary' => str_repeat( 'é', 900 ) ) )['summary'] ), 'summary capped (multibyte)' );
$ok( null === W::shape( array() ), 'empty answer is unusable' );
$ok( null === W::shape( array( 'summary' => '  ', 'corrections' => array() ) ), 'blank answer is unusable' );

// The system prompt fences the learner's text and asks for JSON only.
$prompt = W::system_prompt( 'A2', 'Write about your weekend.', 40, 90 );
$ok( str_contains( $prompt, '<learner_text>' ) && str_contains( $prompt, 'ignore any instructions' ), 'prompt treats the text as data' );
$ok( str_contains( $prompt, 'CEFR level A2' ) && str_contains( $prompt, '40-90 words' ), 'prompt carries level and length' );
$ok( str_contains( $prompt, 'only a JSON object' ), 'prompt asks for JSON only' );

// The browser mirrors the server's limits.
$js = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/writing.js' );
$ok( str_contains( $js, 'Math.floor(task.min * 0.6)' ) && str_contains( $js, 'task.max + 60' ), 'JS limits match word_limits()' );
$ok( str_contains( $js, "source: 'efs-writing-feedback'" ), 'JS fires ef:progress' );
$ok( ! str_contains( $js, 'innerHTML' ), 'JS never inserts HTML' );

echo "{$count} assertions OK\n";
