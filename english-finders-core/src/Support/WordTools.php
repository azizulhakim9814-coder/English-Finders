<?php
/**
 * Pure word utilities shared by games and importers.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Support;

if ( ! defined( 'ABSPATH' ) && ! defined( 'WUC_TESTING' ) ) {
	exit;
}

/*
 * Not final: Word Games Pro subclasses this to keep its original class name and
 * namespace while the implementation lives here, so its existing call sites did
 * not all have to change in one release. Marking it final would make that
 * subclass a fatal error on load.
 */
class WordTools {
	public const MIN_SUPPORTED_LENGTH = 2;
	public const MAX_SUPPORTED_LENGTH = 15;
	public const SUPPORTED_LENGTH_PATTERN = '(?:[2-9]|1[0-5])';

	private const TILE_VALUES = array(
		'a' => 1, 'b' => 3, 'c' => 3, 'd' => 2, 'e' => 1, 'f' => 4, 'g' => 2, 'h' => 4, 'i' => 1,
		'j' => 8, 'k' => 5, 'l' => 1, 'm' => 3, 'n' => 1, 'o' => 1, 'p' => 3, 'q' => 10, 'r' => 1,
		's' => 1, 't' => 1, 'u' => 1, 'v' => 4, 'w' => 4, 'x' => 8, 'y' => 4, 'z' => 10,
	);

	/** @return list<int> */
	public static function supported_lengths(): array {
		return range( self::MIN_SUPPORTED_LENGTH, self::MAX_SUPPORTED_LENGTH );
	}

	public static function clamp_supported_length( int $length ): int {
		return min( self::MAX_SUPPORTED_LENGTH, max( self::MIN_SUPPORTED_LENGTH, $length ) );
	}

	public static function is_supported_length( int|string $length ): bool {
		if ( is_string( $length ) && ! preg_match( '/^\d+$/', $length ) ) {
			return false;
		}

		$length = (int) $length;
		return $length >= self::MIN_SUPPORTED_LENGTH && $length <= self::MAX_SUPPORTED_LENGTH;
	}

	public static function normalize( string $word ): string {
		$word = strtolower( remove_accents( trim( $word ) ) );
		return (string) preg_replace( '/[^a-z]/', '', $word );
	}

	public static function signature( string $word ): string {
		$letters = str_split( self::normalize( $word ) );
		sort( $letters, SORT_STRING );
		return implode( '', $letters );
	}

	public static function scrabble_score( string $word ): int {
		$score = 0;
		foreach ( str_split( self::normalize( $word ) ) as $letter ) {
			$score += self::TILE_VALUES[ $letter ] ?? 0;
		}
		return $score;
	}

	public static function tile_value( string $letter ): int {
		$letter = strtolower( substr( $letter, 0, 1 ) );
		return self::TILE_VALUES[ $letter ] ?? 0;
	}

	public static function rack_score( string $word, string $letters, int $blanks = 0 ): int {
		$available = self::counts( $letters );
		$score     = 0;
		$used_blanks = 0;
		foreach ( str_split( self::normalize( $word ) ) as $letter ) {
			if ( ( $available[ $letter ] ?? 0 ) > 0 ) {
				--$available[ $letter ];
				$score += self::tile_value( $letter );
			} elseif ( $used_blanks < $blanks ) {
				++$used_blanks;
			} else {
				return 0;
			}
		}
		return $score;
	}

	public static function letter_mask( string $word ): int {
		$mask = 0;
		foreach ( array_unique( str_split( self::normalize( $word ) ) ) as $letter ) {
			$offset = ord( $letter ) - 97;
			if ( $offset >= 0 && $offset <= 25 ) {
				$mask |= 1 << $offset;
			}
		}
		return $mask;
	}

	/** @return array<string,int> */
	public static function counts( string $word ): array {
		$counts = array();
		foreach ( str_split( self::normalize( $word ) ) as $letter ) {
			$counts[ $letter ] = ( $counts[ $letter ] ?? 0 ) + 1;
		}
		return $counts;
	}

	public static function can_build( string $word, string $letters, int $blanks = 0 ): bool {
		$available = self::counts( $letters );
		$needed    = self::counts( $word );
		$missing   = 0;

		foreach ( $needed as $letter => $quantity ) {
			$missing += max( 0, $quantity - ( $available[ $letter ] ?? 0 ) );
			if ( $missing > $blanks ) {
				return false;
			}
		}

		return true;
	}

	/** @return list<string> */
	public static function grams( string $word ): array {
		$word = self::normalize( $word );
		if ( strlen( $word ) < 3 ) {
			return array();
		}

		$grams = array();
		for ( $index = 0, $length = strlen( $word ) - 2; $index < $length; $index++ ) {
			$grams[] = substr( $word, $index, 3 );
		}

		return array_values( array_unique( $grams ) );
	}

	public static function wildcard_to_like( string $pattern ): string {
		$pattern = strtolower( trim( $pattern ) );
		$pattern = preg_replace( '/[^a-z*?]/', '', $pattern ) ?? '';
		$pattern = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $pattern );
		return str_replace( array( '*', '?' ), array( '%', '_' ), $pattern );
	}
}
