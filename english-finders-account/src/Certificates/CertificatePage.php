<?php
/**
 * The printable certificate / verification page (0.11.0).
 *
 * URL: /?efa_certificate=CODE -- a query variable rather than a rewrite
 * rule, so nothing depends on flushing permalinks on a live site. Anyone
 * with the link sees the certificate, which is the point: a learner can
 * share it and whoever receives it can check it's genuine. The code is 12
 * random characters, so certificates can't be guessed or listed.
 *
 * Rendered as a standalone page (not inside the theme) so it prints cleanly
 * on one landscape page; "Print / save as PDF" uses the browser's own print
 * dialog -- no PDF library. Never cached (a certificate removed by a
 * privacy erasure must stop verifying at once) and never indexed.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount\Certificates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificatePage {
	public const QUERY_VAR = 'efa_certificate';

	public function register_hooks(): void {
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 1 );
	}

	/** @param list<string> $vars */
	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	public static function url( string $code ): string {
		return add_query_arg( self::QUERY_VAR, rawurlencode( $code ), home_url( '/' ) );
	}

	public function maybe_render(): void {
		$code = (string) get_query_var( self::QUERY_VAR );
		if ( '' === $code ) {
			return;
		}

		$this->render( $code );
		exit;
	}

	/** Sends the headers and prints the page for one code (split from maybe_render() so it can be tested without exit). */
	public function render( string $code ): void {
		$repo        = CertificatesController::repository();
		$certificate = null !== $repo ? $repo->find_by_code( sanitize_text_field( $code ) ) : null;

		// Verification must reflect the database right now, never a cached copy.
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'english finders certificate page' );
		header( 'X-Robots-Tag: noindex, nofollow', true );
		if ( null === $certificate ) {
			status_header( 404 );
		}

		$data = null !== $certificate ? CertificatesController::shape( $certificate ) : null;
		include EFA_PATH . 'templates/public/certificate-page.php';
	}
}
