<?php
/**
 * Profile photos (0.13.0).
 *
 * Optional at sign-up, changeable/removable in Profile. JPG or PNG only,
 * up to 2 MB (browsers shrink it to ~100 KB before upload, 0.13.1). The upload is never served as-is: it is decoded and
 * re-encoded by WordPress's image editor as a centre-cropped square of
 * at most 256x256 (smaller photos keep their size, 0.13.2), which also drops EXIF metadata (phone photos carry GPS
 * location). Stored outside the Media Library, in uploads/efa-avatars/, one
 * file per user with a random suffix (so a replaced photo gets a new URL
 * and no browser shows the old one).
 *
 * Used site-wide through `pre_get_avatar_data`, so get_avatar() -- comments,
 * the admin bar, anything else -- shows the same photo. A photo from Google
 * sign-in is used when the learner hasn't uploaded one.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Profile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Avatar {
	/** 2 MB (0.13.2: back from 5 MB, which strained the server). The browser normally shrinks photos to ~100 KB first -- see assets/js/avatar-picker.js. */
	public const MAX_BYTES = 2097152;

	/** Stored photos are at most this many pixels square (smaller photos keep their own size). */
	public const SIZE = 256;

	/** Photos with a short side under this are refused as too small to show. */
	public const MIN_SIDE = 64;

	/** Uploaded file name (relative to the avatars dir). */
	public const FILE_META = 'efa_avatar_file';

	/** Photo URL from Google sign-in, used only when there is no upload. */
	public const REMOTE_META = 'efa_avatar_remote';

	private const DIR = 'efa-avatars';

	/** Detected MIME type => saved extension. */
	private const TYPES = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
	);

	public function register_hooks(): void {
		add_filter( 'pre_get_avatar_data', array( $this, 'filter_avatar_data' ), 10, 2 );
	}

	/**
	 * Checks a `$_FILES` entry without keeping anything.
	 *
	 * @param array<string,mixed> $file
	 * @return string|null Error code ('avatar_too_big' | 'avatar_invalid' | 'avatar_too_small'), or null when the file is usable.
	 */
	public static function validate_upload( array $file ): ?string {
		if ( (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) === UPLOAD_ERR_INI_SIZE || (int) ( $file['error'] ?? 0 ) === UPLOAD_ERR_FORM_SIZE || (int) ( $file['size'] ?? 0 ) > self::MAX_BYTES ) {
			return 'avatar_too_big';
		}

		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			return 'avatar_invalid';
		}

		if ( null === self::detected_type( (string) $file['tmp_name'], (string) ( $file['name'] ?? '' ) ) ) {
			return 'avatar_invalid';
		}

		// 0.13.2: checked here too, so sign-up refuses a tiny photo before the account exists.
		$info = @getimagesize( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		return is_array( $info ) && min( (int) $info[0], (int) $info[1] ) < self::MIN_SIDE ? 'avatar_too_small' : null;
	}

	/** Was a file actually chosen in this form field? */
	public static function was_submitted( ?array $file ): bool {
		return is_array( $file ) && UPLOAD_ERR_NO_FILE !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
	}

	/**
	 * Validates, crops and stores the upload as this user's photo, replacing
	 * any previous one.
	 *
	 * @param array<string,mixed> $file
	 * @return string|null Error code, or null on success.
	 */
	public function store_upload( int $user_id, array $file ): ?string {
		$error = self::validate_upload( $file );
		if ( null !== $error ) {
			return $error;
		}

		$type = (string) self::detected_type( (string) $file['tmp_name'], (string) ( $file['name'] ?? '' ) );
		$dir  = self::dir();
		if ( '' === $dir || ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) ) {
			return 'avatar_invalid';
		}
		self::protect_dir( $dir );

		$editor = wp_get_image_editor( (string) $file['tmp_name'] );
		if ( is_wp_error( $editor ) ) {
			return 'avatar_unreadable';
		}

		/*
		 * 0.13.2: centre square crop done explicitly. The editor's own
		 * resize( 256, 256, true ) never enlarges, so for any photo whose
		 * short side is under 256px (e.g. a 120x150 passport photo) it
		 * returned "Could not calculate resized image dimensions" and the
		 * upload failed; for e.g. 300x200 it produced a non-square 256x200.
		 * Small photos are kept at their own size, never upscaled.
		 */
		$size = $editor->get_size();
		$w    = (int) ( $size['width'] ?? 0 );
		$h    = (int) ( $size['height'] ?? 0 );
		$side = min( $w, $h );
		if ( $side < self::MIN_SIDE ) {
			return 'avatar_too_small';
		}
		$out = min( self::SIZE, $side );
		if ( is_wp_error( $editor->crop( intdiv( $w - $side, 2 ), intdiv( $h - $side, 2 ), $side, $side, $out, $out ) ) ) {
			return 'avatar_unreadable';
		}

		$name  = $user_id . '-' . strtolower( wp_generate_password( 10, false ) ) . '.' . self::TYPES[ $type ];
		$saved = $editor->save( $dir . '/' . $name, $type );
		if ( is_wp_error( $saved ) ) {
			return 'avatar_unreadable';
		}

		$this->delete_file( $user_id );
		update_user_meta( $user_id, self::FILE_META, $name );

		return null;
	}

	/** Remembers the Google photo URL (https on Google's own image host only). */
	public static function remember_remote( int $user_id, string $url ): void {
		if ( self::is_allowed_remote( $url ) ) {
			update_user_meta( $user_id, self::REMOTE_META, esc_url_raw( $url ) );
		}
	}

	/** The photo URL to show, or '' for none. */
	public function url( int $user_id ): string {
		$file = (string) get_user_meta( $user_id, self::FILE_META, true );
		if ( '' !== $file && is_file( self::dir() . '/' . $file ) ) {
			$uploads = wp_upload_dir( null, false );

			return trailingslashit( (string) $uploads['baseurl'] ) . self::DIR . '/' . rawurlencode( $file );
		}

		$remote = (string) get_user_meta( $user_id, self::REMOTE_META, true );

		return self::is_allowed_remote( $remote ) ? $remote : '';
	}

	/** Removes the uploaded photo and the remembered Google one. */
	public function delete( int $user_id ): void {
		$this->delete_file( $user_id );
		delete_user_meta( $user_id, self::REMOTE_META );
	}

	/**
	 * @param array<string,mixed> $args
	 * @param mixed               $id_or_email
	 * @return array<string,mixed>
	 */
	public function filter_avatar_data( $args, $id_or_email ) {
		$user_id = self::user_id_of( $id_or_email );
		if ( $user_id > 0 ) {
			$url = $this->url( $user_id );
			if ( '' !== $url ) {
				$args['url']          = $url;
				$args['found_avatar'] = true;
			}
		}

		return $args;
	}

	private function delete_file( int $user_id ): void {
		$file = (string) get_user_meta( $user_id, self::FILE_META, true );
		// basename(): the meta value is ours, but never let it point outside the avatars dir.
		if ( '' !== $file && is_file( self::dir() . '/' . basename( $file ) ) ) {
			wp_delete_file( self::dir() . '/' . basename( $file ) );
		}
		delete_user_meta( $user_id, self::FILE_META );
	}

	/** JPEG or PNG by the file's actual content (not its name or the browser's claim), or null. */
	private static function detected_type( string $path, string $name ): ?string {
		$check = wp_check_filetype_and_ext( $path, $name, self::TYPES_BY_EXT );
		$info  = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a non-image is an expected, handled case.
		$mime  = is_array( $info ) ? (string) ( $info['mime'] ?? '' ) : '';

		if ( ! isset( self::TYPES[ $mime ] ) || empty( $check['type'] ) || $check['type'] !== $mime ) {
			return null;
		}

		return $mime;
	}

	/** Allowed extensions for wp_check_filetype_and_ext(). */
	private const TYPES_BY_EXT = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
	);

	private static function is_allowed_remote( string $url ): bool {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );

		return str_starts_with( $url, 'https://' ) && ( str_ends_with( $host, '.googleusercontent.com' ) );
	}

	private static function dir(): string {
		$uploads = wp_upload_dir( null, false );

		return empty( $uploads['basedir'] ) ? '' : untrailingslashit( (string) $uploads['basedir'] ) . '/' . self::DIR;
	}

	/** No directory listing. */
	private static function protect_dir( string $dir ): void {
		if ( ! is_file( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	/** @param mixed $id_or_email */
	private static function user_id_of( $id_or_email ): int {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}
		if ( $id_or_email instanceof \WP_User ) {
			return (int) $id_or_email->ID;
		}
		if ( $id_or_email instanceof \WP_Post ) {
			return (int) $id_or_email->post_author;
		}
		if ( $id_or_email instanceof \WP_Comment ) {
			return (int) $id_or_email->user_id;
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );

			return $user ? (int) $user->ID : 0;
		}

		return 0;
	}
}
