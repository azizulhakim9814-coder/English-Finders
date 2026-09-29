<?php
/**
 * The actual page template for /my-account/, swapped in via template_include.
 *
 * This file exists specifically so WordPress's normal template-loader.php
 * sequence completes around it -- get_header()/get_footer() here run inside
 * the standard request lifecycle, not short-circuited early. That distinction
 * is what a live bug traced back to: an earlier version of this rendering
 * used template_redirect + exit, which skipped that lifecycle entirely and
 * broke the site's Elementor-built footer on this one page (it fell back to
 * the theme's plain default footer, since Elementor's Theme Builder hooks
 * into hooks that only fire when the request completes normally). See
 * MyAccountController::maybe_override_template().
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

use EnglishFindersAccount\Certificates\CertificatesController;
use EnglishFindersAccount\Leaderboard\LeaderboardController;
use EnglishFindersAccount\Level\LevelController;
use EnglishFindersAccount\Library\LibraryController;
use EnglishFindersAccount\Membership\MembershipController;
use EnglishFindersAccount\Mistakes\MistakesController;
use EnglishFindersAccount\Pages\HomeController;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Progress\ProgressController;
use EnglishFindersAccount\Pages\AuthView;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( is_user_logged_in() ) {
	$user       = wp_get_current_user();
	$profile    = ( new ProfileRepository() )->get_all( $user->ID );
	$membership = ( new MembershipController() )->data_for_user( $user->ID );
	$progress   = ( new ProgressController() )->data_for_user( $user->ID );
	$library    = ( new LibraryController() )->data_for_user( $user->ID );
	$level       = ( new LevelController() )->data_for_user( $user->ID );
	$leaderboard = ( new LeaderboardController() )->data_for_user( $user->ID );
	$certificates = ( new CertificatesController() )->data_for_user( $user->ID );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, not a state change.
	$mistakes   = ( new MistakesController() )->data_for_user( $user->ID, isset( $_GET[ MistakesController::SKILL_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ MistakesController::SKILL_PARAM ] ) ) : '' );
	$home       = ( new HomeController() )->data_for_user( $user->ID, $level, $progress, $mistakes );
	include EFA_PATH . 'templates/public/account-shell.php';
} else {
	// 0.14.0: error, form, Turnstile key, validated return address (Urls::return_target()) and kept sign-up values -- shared with the standalone pages.
	$efa_view           = AuthView::from_request();
	$error              = $efa_view['error'];
	$form               = $efa_view['form'];
	$turnstile_site_key = $efa_view['turnstile_site_key'];
	$return             = $efa_view['return'];
	$draft              = $efa_view['draft'];
	include EFA_PATH . 'templates/public/login-register.php';
}

get_footer();
