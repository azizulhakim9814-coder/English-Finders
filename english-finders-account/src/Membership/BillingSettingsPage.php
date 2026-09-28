<?php
/**
 * Settings -> English Finders Pro (0.18.0): the switch that puts Pro on sale.
 *
 * Stores only public Paddle values in Core's `efc_settings` (environment,
 * client-side token, the monthly / yearly price ids); the API key and
 * webhook secret stay in wp-config.php and are only reported as set / not
 * set. Shows a checklist, the webhook address and events to enter in
 * Paddle, and whether Pro is on sale (ProOffer::on_sale()).
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BillingSettingsPage {
	public const PAGE   = 'efa-pro-settings';
	public const ACTION = 'efa_save_pro_settings';

	/** Events Core's webhook handles. */
	public const EVENTS = array( 'subscription.created', 'subscription.updated', 'subscription.canceled', 'subscription.past_due', 'subscription.paused', 'transaction.completed' );

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	public function menu(): void {
		add_options_page(
			__( 'English Finders Pro', 'english-finders-account' ),
			__( 'English Finders Pro', 'english-finders-account' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to view this page.', 'english-finders-account' ) );
		}

		$environment = ProOffer::environment();
		$token       = ProOffer::client_token();
		$prices      = ProOffer::prices();
		$checks      = self::checks();
		$on_sale     = ProOffer::on_sale();
		$webhook     = rest_url( 'efc/v1/paddle-webhook' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only result of the save redirect.
		$result      = isset( $_GET['efa_saved'] ) ? sanitize_key( wp_unslash( $_GET['efa_saved'] ) ) : '';
		$promo       = '1' === (string) get_option( ProOffer::PROMO_OPTION, '0' );

		include EFA_PATH . 'templates/admin/pro-settings.php';
	}

	/** @return array<string,bool> checklist item => done */
	public static function checks(): array {
		$prices = ProOffer::prices();

		return array(
			'core'     => ProOffer::core_available(),
			'secret'   => ProOffer::webhook_secret_set(),
			'api_key'  => ProOffer::api_key_set(),
			'token'    => '' !== ProOffer::client_token(),
			'prices'   => '' !== $prices['month'] || '' !== $prices['year'],
			'match'    => self::token_matches( ProOffer::client_token(), ProOffer::environment() ),
		);
	}

	/** Paddle client-side tokens start with test_ (sandbox) or live_ (production). */
	public static function token_matches( string $token, string $environment ): bool {
		if ( '' === $token ) {
			return true;
		}

		return str_starts_with( $token, 'production' === $environment ? 'live_' : 'test_' );
	}

	public static function valid_price_id( string $id ): bool {
		return '' === $id || 1 === preg_match( '/^pri_[a-z0-9]{10,60}$/', $id );
	}

	public static function valid_token( string $token ): bool {
		return '' === $token || 1 === preg_match( '/^(test|live)_[A-Za-z0-9]{10,80}$/', $token );
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'english-finders-account' ) );
		}
		check_admin_referer( self::ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$environment = isset( $_POST['environment'] ) && 'production' === $_POST['environment'] ? 'production' : 'sandbox';
		$token       = isset( $_POST['client_token'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['client_token'] ) ) ) : '';
		$month       = isset( $_POST['price_month'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['price_month'] ) ) ) : '';
		$year        = isset( $_POST['price_year'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['price_year'] ) ) ) : '';
		// phpcs:enable

		if ( ! self::valid_token( $token ) || ! self::valid_price_id( $month ) || ! self::valid_price_id( $year ) || ( '' !== $month && $month === $year ) ) {
			$this->back( 'invalid' );
		}

		$settings                             = ProOffer::settings();
		$settings['paddle_environment']       = $environment;
		$settings['paddle_client_side_token'] = $token;
		$settings['paddle_price_plan_map']    = self::merged_map( $settings, $month, $year );
		$settings['paddle_price_intervals']   = array_filter(
			array(
				$month => 'month',
				$year  => 'year',
			),
			static fn ( $interval, $id ) => '' !== (string) $id,
			ARRAY_FILTER_USE_BOTH
		);
		update_option( 'efc_settings', $settings );
		// 0.19.0: the "Go Pro" links switch.
		update_option( ProOffer::PROMO_OPTION, ! empty( $_POST['promo'] ) ? '1' : '0', false ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.

		$this->back( self::token_matches( $token, $environment ) ? 'saved' : 'mismatch' );
	}

	/**
	 * The webhook's price => plan map with this page's Pro prices in it.
	 * Other plans (e.g. teacher_pro) and old prices are kept, so a renewal on
	 * a price that is no longer sold still maps to Pro.
	 *
	 * @param array<string,mixed> $settings
	 * @return array<string,string>
	 */
	public static function merged_map( array $settings, string $month, string $year ): array {
		$map = is_array( $settings['paddle_price_plan_map'] ?? null ) ? $settings['paddle_price_plan_map'] : array();
		foreach ( array( $month, $year ) as $id ) {
			if ( '' !== $id ) {
				$map[ $id ] = 'pro';
			}
		}

		return $map;
	}

	private function back( string $result ): void {
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'efa_saved' => $result ), admin_url( 'options-general.php' ) ) );
		exit;
	}
}
