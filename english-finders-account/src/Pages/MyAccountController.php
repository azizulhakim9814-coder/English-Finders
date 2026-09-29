<?php
/**
 * Routes /my-account/ to our own template, replacing the broken WooCommerce
 * shortcode.
 *
 * Matched via `template_include`, not `template_redirect` + exit. The
 * earlier version of this class used template_redirect, which broke this
 * site's Elementor-built global footer on this one page -- confirmed live
 * (2026-09-22): every other classic (non-Elementor-content) page, e.g.
 * /privacy-policy/, correctly got the full Elementor footer, but my-account
 * fell back to the theme's plain default. The difference was never "classic
 * vs Elementor content" (both are classic pages) -- it was that
 * template_redirect + exit skipped WordPress's normal template-loader.php
 * sequence entirely, which is what Elementor's Theme Builder hooks into.
 * template_include is the correct extension point for "swap the template
 * file, keep everything else about the normal request lifecycle intact" --
 * it is also what Elementor's own full-page templates use internally.
 *
 * The page (WP page ID 7276 as of the 2026-09-21 audit) currently holds the
 * [woocommerce_my_account] shortcode as its stored content, which is why a
 * live-site content edit is still not required to deploy this: the swapped
 * template never reads that stored content at all.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Pages;

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MyAccountController {
	/**
	 * Matched by page slug rather than a hardcoded ID: portable across a
	 * staging/live pair where the same page can have different IDs, and
	 * readable without cross-referencing the audit for the number.
	 */
	private const PAGE_SLUG = 'my-account';

	public function register_hooks(): void {
		add_filter( 'template_include', array( $this, 'maybe_override_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles' ) );
		add_filter( 'litespeed_optimize_js_excludes', array( $this, 'exclude_from_js_optimizer' ) );
		add_action( 'template_redirect', array( $this, 'maybe_return_logged_in' ) );
	}

	/**
	 * 0.12.0: a learner sent here from a lesson/quiz ("Log in to continue")
	 * who is already logged in -- e.g. in another tab -- goes straight back
	 * instead of seeing their dashboard. Redirecting (not rendering) is fine
	 * on template_redirect; the Elementor-footer caveat above applies only
	 * to pages that are actually rendered.
	 */
	public function maybe_return_logged_in(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation only; validated by Urls::return_target().
		if ( ! is_user_logged_in() || ! is_page( self::PAGE_SLUG ) || ! isset( $_GET[ Urls::RETURN_PARAM ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$return = Urls::return_target( (string) wp_unslash( $_GET[ Urls::RETURN_PARAM ] ) );
		if ( '' !== $return ) {
			wp_safe_redirect( $return );
			exit;
		}
	}

	public function maybe_override_template( string $template ): string {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return $template;
		}

		return EFA_PATH . 'templates/public/my-account-template.php';
	}

	/**
	 * Loads once, for both the logged-in and logged-out views of this page
	 * -- unlike RegistrationHandler's Turnstile script (register-form-only),
	 * this stylesheet covers every section of the page.
	 */
	public function maybe_enqueue_styles(): void {
		// 0.14.0: also the standalone [efa_login] / [efa_signup] pages.
		if ( ! AuthPages::needs_account_assets() ) {
			return;
		}

		wp_enqueue_style( 'efa-account', EFA_URL . 'assets/css/account.css', array(), EFA_VERSION );

		// 0.14.0: Show/Hide buttons on password fields.
		wp_enqueue_script( 'efa-auth-forms', EFA_URL . 'assets/js/auth-forms.js', array(), EFA_VERSION, true );
		wp_localize_script(
			'efa-auth-forms',
			'efaAuthForms',
			array(
				'show'     => __( 'Show', 'english-finders-account' ),
				'hide'     => __( 'Hide', 'english-finders-account' ),
				// 0.14.3: live check on "Confirm password".
				'match'    => __( 'Passwords match.', 'english-finders-account' ),
				'mismatch' => __( "Passwords don't match yet.", 'english-finders-account' ),
			)
		);

		// 0.13.1: photo preview + in-browser shrink (sign-up and Profile both have a photo field).
		wp_enqueue_script( 'efa-avatar-picker', EFA_URL . 'assets/js/avatar-picker.js', array(), EFA_VERSION, true );
		wp_localize_script(
			'efa-avatar-picker',
			'efaAvatarPicker',
			array(
				// 0.13.2: the same limits the server enforces (Profile\Avatar), so the browser can say so before uploading.
				'maxBytes' => \EnglishFindersAccount\Profile\Avatar::MAX_BYTES,
				'minSide'  => \EnglishFindersAccount\Profile\Avatar::MIN_SIDE,
				'i18n'     => array(
					'tooBig'     => __( 'This photo is larger than 2 MB. Please choose a smaller one.', 'english-finders-account' ),
					'tooSmall'   => __( 'This photo is too small. Please use one at least 64 pixels wide and tall.', 'english-finders-account' ),
					'wrongType'  => __( 'Please choose a JPG or PNG image.', 'english-finders-account' ),
					'preparing'  => __( 'Preparing your photo…', 'english-finders-account' ),
					'ready'      => __( 'Looks good. It is saved when you submit the form.', 'english-finders-account' ),
					'unreadable' => __( 'Photo selected. It is checked when you submit the form.', 'english-finders-account' ),
					'saving'     => __( 'Saving…', 'english-finders-account' ),
				),
			)
		);
	}

	/**
	 * LiteSpeed's JS minifier has corrupted scripts on this site before
	 * (see the Word Games Pro audio fix); this one is tiny, so skip it.
	 *
	 * @param mixed $excludes
	 * @return mixed
	 */
	public function exclude_from_js_optimizer( $excludes ) {
		if ( is_array( $excludes ) ) {
			$excludes[] = 'english-finders-account/assets/js/';
		}

		return $excludes;
	}
}
