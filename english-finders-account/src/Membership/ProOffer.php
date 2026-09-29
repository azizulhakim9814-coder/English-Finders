<?php
/**
 * Pro, in one place (0.18.0): who has it, what it costs, and whether it is
 * on sale.
 *
 * Owner's decisions 2026-09-26: Pro = no ads + 5 streak freezes topped up
 * every month (English Finders Core 1.13.0); $2.99/month or $24.99/year;
 * built behind a switch.
 *
 * The switch is the Paddle configuration (Settings -> English Finders Pro):
 * Pro is on sale once there is a client-side token and at least one Pro
 * price. The secrets (API key, webhook secret) stay in wp-config.php.
 *
 * Settings live in Core's `efc_settings` option, next to the keys Core's
 * webhook already reads:
 *  - paddle_environment        'sandbox' | 'production'
 *  - paddle_client_side_token  public token for Paddle.js
 *  - paddle_price_plan_map     price id => plan code (the webhook's map)
 *  - paddle_price_intervals    price id => 'month' | 'year' (0.18.0)
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Membership;

use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProOffer {
	/** Core 1.6.0 added the checkout/portal services Pro relies on. */
	public const MIN_CORE_VERSION = '1.6.0';

	public const INTERVALS = array( 'month', 'year' );

	/** 0.19.0: '1' when the "Go Pro" links around the site are switched on. */
	public const PROMO_OPTION = 'efa_pro_promo';

	/** @return array<string,mixed> */
	public static function settings(): array {
		$settings = get_option( 'efc_settings', array() );

		return is_array( $settings ) ? $settings : array();
	}

	public static function core_available(): bool {
		return class_exists( '\\EnglishFindersCore\\Support\\Api' ) && Api::is_at_least( self::MIN_CORE_VERSION );
	}

	public static function environment(): string {
		return 'production' === ( self::settings()['paddle_environment'] ?? '' ) ? 'production' : 'sandbox';
	}

	public static function client_token(): string {
		return trim( (string) ( self::settings()['paddle_client_side_token'] ?? '' ) );
	}

	/**
	 * The Pro price id for each billing interval ('' when not set).
	 *
	 * @return array{month:string,year:string}
	 */
	public static function prices(): array {
		$settings  = self::settings();
		$map       = is_array( $settings['paddle_price_plan_map'] ?? null ) ? $settings['paddle_price_plan_map'] : array();
		$intervals = is_array( $settings['paddle_price_intervals'] ?? null ) ? $settings['paddle_price_intervals'] : array();
		$out       = array(
			'month' => '',
			'year'  => '',
		);
		foreach ( $map as $price_id => $plan ) {
			$interval = (string) ( $intervals[ $price_id ] ?? '' );
			if ( 'pro' === $plan && in_array( $interval, self::INTERVALS, true ) && '' === $out[ $interval ] ) {
				$out[ $interval ] = (string) $price_id;
			}
		}

		return $out;
	}

	/**
	 * On sale: Core is there, Paddle.js has a token, at least one Pro price
	 * is set, and the webhook secret is in place -- without it Core could
	 * not verify Paddle's payment notices, so a buyer would never get Pro.
	 */
	public static function on_sale(): bool {
		$prices = self::prices();

		return self::core_available() && '' !== self::client_token() && ( '' !== $prices['month'] || '' !== $prices['year'] ) && self::webhook_secret_set();
	}

	/**
	 * The "Go Pro" links in the header and under articles (0.19.0): only
	 * when Pro is on sale AND the owner has switched them on in Settings ->
	 * English Finders Pro (off by default, so nobody is pointed at a
	 * checkout before it has been tried for real). Filter: efa_pro_promo_enabled.
	 */
	public static function promo_on(): bool {
		return (bool) apply_filters( 'efa_pro_promo_enabled', self::on_sale() && '1' === (string) get_option( self::PROMO_OPTION, '0' ) );
	}

	/**
	 * Daily AI writing checks for Free and Pro (0.21.0), or null while AI
	 * feedback is unavailable (Core older than 1.16.0, or AI switched off in
	 * Core's settings) -- so the plans never promise a feature that is off.
	 * The numbers are Core's settings, not copy here, so they cannot drift.
	 *
	 * @return array{free:int,pro:int}|null
	 */
	public static function ai_allowances(): ?array {
		if ( ! class_exists( '\\EnglishFindersCore\\Support\\Api' ) || ! Api::is_at_least( '1.16.0' ) ) {
			return null;
		}

		$ai = Api::service( 'ai' );
		if ( ! $ai instanceof \EnglishFindersCore\Ai\AiService || ! $ai->is_enabled() ) {
			return null;
		}

		$free = $ai->quota()->daily_limit_for( false );
		$pro  = $ai->quota()->daily_limit_for( true );

		return $pro > 0 ? array(
			'free' => $free,
			'pro'  => $pro,
		) : null;
	}

	/** EFC_PADDLE_WEBHOOK_SECRET in wp-config.php (or Core's setting). Never read out, only checked. */
	public static function webhook_secret_set(): bool {
		return ( defined( 'EFC_PADDLE_WEBHOOK_SECRET' ) && is_string( EFC_PADDLE_WEBHOOK_SECRET ) && '' !== EFC_PADDLE_WEBHOOK_SECRET )
			|| '' !== trim( (string) ( self::settings()['paddle_webhook_secret'] ?? '' ) );
	}

	/** EFC_PADDLE_API_KEY (for "Manage billing"). Never read out, only checked. */
	public static function api_key_set(): bool {
		return ( defined( 'EFC_PADDLE_API_KEY' ) && is_string( EFC_PADDLE_API_KEY ) && '' !== EFC_PADDLE_API_KEY )
			|| '' !== trim( (string) ( self::settings()['paddle_api_key'] ?? '' ) );
	}

	/**
	 * Shown prices. They must match the prices set up in Paddle (Paddle's own
	 * checkout always shows the real amount and currency).
	 *
	 * @return array{month:string,year:string,saving:string}
	 */
	public static function labels(): array {
		$labels = (array) apply_filters(
			'efa_pro_price_labels',
			array(
				'month'  => '$2.99',
				'year'   => '$24.99',
				'saving' => '30%',
			)
		);

		return array(
			'month'  => (string) ( $labels['month'] ?? '' ),
			'year'   => (string) ( $labels['year'] ?? '' ),
			'saving' => (string) ( $labels['saving'] ?? '' ),
		);
	}

	/** Whether a member has Pro now (active Pro or Teacher Pro). Filter: efa_is_pro. */
	public static function is_pro( int $user_id ): bool {
		$pro = false;
		if ( $user_id > 0 && self::core_available() ) {
			$entitlements = Api::service( 'entitlements' );
			$pro          = is_object( $entitlements ) && method_exists( $entitlements, 'for_user' ) && $entitlements->for_user( $user_id )->unlocks_pro();
		}

		return (bool) apply_filters( 'efa_is_pro', $pro, $user_id );
	}

	/**
	 * Paddle.js + the checkout script, for a signed-in member on a page with
	 * an upgrade button (My Account, the pricing page). Nothing loads until
	 * Pro is on sale.
	 */
	public static function enqueue_checkout(): void {
		if ( ! is_user_logged_in() || ! self::on_sale() ) {
			return;
		}

		$user = wp_get_current_user();

		// No version string on Paddle's CDN script: Paddle versions its own URL.
		wp_enqueue_script( 'paddle-js', 'https://cdn.paddle.com/paddle/v2/paddle.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'efa-checkout', EFA_URL . 'assets/js/checkout.js', array( 'paddle-js' ), EFA_VERSION, true );
		wp_localize_script(
			'efa-checkout',
			'efaCheckout',
			array(
				'clientSideToken' => self::client_token(),
				'environment'     => self::environment(),
				'userId'          => (int) $user->ID,
				'email'           => (string) $user->user_email,
				'successUrl'      => add_query_arg( 'efa_notice', 'pro_welcome', \EnglishFindersAccount\Support\Urls::my_account() ) . '#efa-section-membership',
			)
		);
	}
}
