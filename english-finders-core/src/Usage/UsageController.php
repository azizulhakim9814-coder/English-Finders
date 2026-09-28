<?php
/**
 * Collects `ef:progress` usage from the browser (1.15.0).
 *
 * Front end: a small inline script, printed in the footer of every public
 * page, listens for the shared `ef:progress` event that Study tools and
 * Word Games Pro games already dispatch. It adds the events up while the
 * page is open and sends one report when the page is hidden or left
 * (navigator.sendBeacon), so an active page costs one request, not one per
 * answer. The same events go to Google Analytics 4 when Site Kit's `gtag`
 * is on the page: `ef_engaged` once per tool per page, plus `ef_solved` /
 * `ef_finished` (correct answers are too frequent to send one by one).
 *
 * Server: POST efc/v1/usage. No nonce, on purpose, for the same reason as
 * the level test -- pages are served from a long-lived cache, so a nonce
 * printed into one would routinely be expired. The route writes nothing but
 * aggregate counters (see UsageRepository), strictly validated and capped,
 * so the worst a forged request can do is inflate a count.
 *
 * Filter: efc_usage_tracking_enabled (bool) -- false removes the script.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Usage;

use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsageController {
	private const REST_NAMESPACE = 'efc/v1';
	private const ROUTE          = '/usage';

	/** A report is a few hundred bytes; anything much larger is not from our script. */
	private const MAX_BODY_BYTES = 4096;

	public function __construct( private readonly UsageRepository $usage ) {}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
		add_action( 'wp_footer', array( $this, 'print_script' ), 99 );
	}

	public function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		if ( ! self::enabled() ) {
			return new WP_REST_Response( null, 204 );
		}

		if ( ! self::same_site( (string) $request->get_header( 'origin' ) ) ) {
			return new WP_REST_Response( array( 'error' => 'forbidden_origin' ), 403 );
		}

		$body = $request->get_body();
		if ( '' === $body || strlen( $body ) > self::MAX_BODY_BYTES ) {
			return new WP_REST_Response( array( 'error' => 'bad_request' ), 400 );
		}

		/*
		 * sendBeacon posts text/plain (a JSON content type would need a CORS
		 * preflight, which beacons cannot do), so WordPress does not parse the
		 * body into params -- it is decoded here instead.
		 */
		$data   = json_decode( $body, true );
		$counts = UsageRepository::normalize( is_array( $data ) ? ( $data['e'] ?? null ) : null );
		if ( array() === $counts ) {
			return new WP_REST_Response( array( 'error' => 'bad_request' ), 400 );
		}

		$this->usage->add( $counts );

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * A browser always sends Origin with a beacon; when present it must be
	 * this site. Absent Origin is allowed (some privacy tools strip it).
	 */
	private static function same_site( string $origin ): bool {
		if ( '' === $origin ) {
			return true;
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return is_string( $host ) && strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) ) === strtolower( $host );
	}

	public static function enabled(): bool {
		return (bool) apply_filters( 'efc_usage_tracking_enabled', true );
	}

	public function print_script(): void {
		if ( ! self::enabled() || is_admin() || is_feed() || is_embed() ) {
			return;
		}

		$config = wp_json_encode(
			array(
				'url'     => rest_url( self::REST_NAMESPACE . self::ROUTE ),
				'pattern' => '^(efs|wgp)-[a-z0-9-]{1,40}$',
				'kinds'   => UsageRepository::REPORTED_KINDS,
			)
		);

		$script = <<<'JS'
(function (c) {
	if (!c || !window.JSON) return;
	var re = new RegExp(c.pattern), counts = {}, pending = 0, seen = {};
	document.addEventListener('ef:progress', function (e) {
		var d = (e && e.detail) || {}, s = String(d.source || ''), k = String(d.kind || '');
		if (!re.test(s) || c.kinds.indexOf(k) < 0) return;
		counts[s + ':' + k] = (counts[s + ':' + k] || 0) + 1;
		pending++;
		if (typeof window.gtag === 'function') {
			if (!seen[s]) { seen[s] = 1; window.gtag('event', 'ef_engaged', { ef_tool: s }); }
			if (k !== 'correct') window.gtag('event', 'ef_' + k, { ef_tool: s });
		}
	});
	function flush() {
		if (!pending) return;
		var body = JSON.stringify({ v: 1, e: counts });
		counts = {}; pending = 0;
		try {
			if (navigator.sendBeacon && navigator.sendBeacon(c.url, new Blob([body], { type: 'text/plain' }))) return;
			if (window.fetch) fetch(c.url, { method: 'POST', body: body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'text/plain' } });
		} catch (err) {}
	}
	document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') flush(); });
	window.addEventListener('pagehide', flush);
})(%s);
JS;

		wp_print_inline_script_tag( sprintf( $script, $config ), array( 'id' => 'efc-usage' ) );
	}
}
