<?php
/**
 * Membership & Billing section of the My Account page (Phase A5).
 *
 * This is where English Finders Account first becomes a real consumer of
 * Core, not just a declared dependency -- Entitlement status, the Paddle
 * Customer Portal link, and the transaction history all come from Core's
 * Billing services (EntitlementRepository, PaddlePortalClient,
 * TransactionLog), resolved through Support\Api::service() the same way
 * Word Games Pro and English Finders Study already do, never by querying
 * Core's tables directly.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

use EnglishFindersAccount\Support\Urls;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MembershipController {
	private const ACTION = 'efa_manage_billing';

	/**
	 * Minimum Core version this class actually calls into -- 1.6.0 is the
	 * release that added PaddlePortalClient, TransactionLog, and
	 * paddle_customer_id_for_user(). Mirrors the class_exists()-plus-
	 * is_at_least() guard Word Games Pro already uses at its own Core call
	 * sites, not a new pattern invented here.
	 */
	private const MIN_CORE_VERSION = '1.6.0';

	public function register_hooks(): void {
		if ( ! $this->core_available() ) {
			return;
		}

		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_manage_billing' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_checkout' ) );
	}

	/**
	 * Load Paddle.js only where it's actually needed: the logged-in
	 * My Account page, and only once a client-side token is configured.
	 * A page that will never open a checkout has no reason to load a
	 * third-party script.
	 */
	public function maybe_enqueue_checkout(): void {
		if ( ! is_user_logged_in() || ! is_page( 'my-account' ) ) {
			return;
		}

		// 0.18.0: one place for Paddle.js + checkout settings (ProOffer), also used by the pricing page.
		ProOffer::enqueue_checkout();
	}

	private function core_available(): bool {
		return class_exists( '\\EnglishFindersCore\\Support\\Api' ) && Api::is_at_least( self::MIN_CORE_VERSION );
	}

	/**
	 * Data for the Membership section template.
	 *
	 * Returns null when Core isn't available or isn't new enough --
	 * MyAccountController/the template treat that as "don't render this
	 * section" rather than fataling.
	 *
	 * @return array{entitlement: object, transactions: list<array<string,mixed>>, checkout: array{client_side_token: string, price_plan_map: array<string,string>}}|null
	 */
	public function data_for_user( int $user_id ): ?array {
		if ( ! $this->core_available() ) {
			return null;
		}

		$entitlements = Api::service( 'entitlements' );
		$transactions = Api::service( 'transactions' );

		if ( null === $entitlements || null === $transactions ) {
			return null;
		}

		$entitlement = $entitlements->for_user( $user_id );
		$customer_id = $entitlements->paddle_customer_id_for_user( $user_id );

		return array(
			'entitlement'  => $entitlement,
			'transactions' => '' !== $customer_id ? $transactions->for_customer( $customer_id ) : array(),
			'checkout'     => $this->checkout_config(),
			// 0.18.0: monthly / yearly Pro prices, shown labels, and whether Pro is on sale.
			'offer'        => array(
				'on_sale' => ProOffer::on_sale(),
				'prices'  => ProOffer::prices(),
				'labels'  => ProOffer::labels(),
			),
		);
	}

	/**
	 * @return array{client_side_token: string, price_plan_map: array<string,string>}
	 */
	private function checkout_config(): array {
		/*
		 * Read directly from Core's `efc_settings` option rather than
		 * through a dedicated Core service method. Unlike the entitlement
		 * table (Core's real internal data, always reached through
		 * EntitlementRepository), this is plain WordPress option storage,
		 * and both values here are meant to be exposed anyway -- the
		 * client-side token is designed for browser JS, and the price map
		 * is just plan-code labels. Adding a facade method for two
		 * already-public values would be indirection without a purpose.
		 */
		$settings = get_option( 'efc_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		$price_plan_map = is_array( $settings['paddle_price_plan_map'] ?? null ) ? $settings['paddle_price_plan_map'] : array();

		return array(
			'client_side_token' => (string) ( $settings['paddle_client_side_token'] ?? '' ),
			'price_plan_map'    => $price_plan_map,
		);
	}

	public function handle_manage_billing(): void {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to do that.', 'english-finders-account' ) );
		}

		check_admin_referer( self::ACTION );

		if ( ! $this->core_available() ) {
			wp_safe_redirect( add_query_arg( 'efa_notice', 'billing_unavailable', Urls::my_account() ) );
			exit;
		}

		$entitlements = Api::service( 'entitlements' );
		$portal       = Api::service( 'paddle_portal' );

		if ( null === $entitlements || null === $portal ) {
			wp_safe_redirect( add_query_arg( 'efa_notice', 'billing_unavailable', Urls::my_account() ) );
			exit;
		}

		$customer_id = $entitlements->paddle_customer_id_for_user( get_current_user_id() );
		$session_url = $portal->create_session( $customer_id );

		if ( is_wp_error( $session_url ) ) {
			wp_safe_redirect( add_query_arg( 'efa_notice', 'portal_error', Urls::my_account() ) );
			exit;
		}

		wp_safe_redirect( $session_url );
		exit;
	}
}
