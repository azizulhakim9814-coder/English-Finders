<?php
/**
 * Standalone login / sign-up page body (0.14.0) -- rendered by the
 * [efa_login] and [efa_signup] shortcodes (Pages\AuthPages).
 *
 * One focused form with a heading and a link to the other page, next to a
 * brand panel whose numbers are counted from the site itself. On phones the
 * panel folds away so the form comes first.
 *
 * @package EnglishFindersAccount
 *
 * @var string $mode               'login' | 'signup'.
 * @var string $error              Error code from a failed attempt, or ''.
 * @var string $form               Which form the error belongs to ('login'|'register').
 * @var string $turnstile_site_key Cloudflare Turnstile site key, or ''.
 * @var string $return             Validated page to go to afterwards, or ''.
 * @var string $origin             This page's URL: mistakes come back here.
 * @var array{name: string, email: string, account_type: string} $draft
 * @var bool   $show_panel
 * @var bool   $logged_in          Only true in the Elementor editor/preview (members are redirected otherwise).
 * @var string $display_name
 */

declare(strict_types=1);

use EnglishFindersAccount\Pages\AuthView;
use EnglishFindersAccount\Support\Icons;
use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_login      = 'login' === $mode;
$error_message = AuthView::message( $error );
$google_error  = str_starts_with( $error, 'google_' );
$show_error    = '' !== $error_message && ( $google_error || ( $is_login ? 'login' === $form : 'register' === $form ) );
$facts         = AuthView::site_facts();
?>
<div class="efa-auth-page efa-auth-standalone<?php echo $show_panel ? ' has-panel' : ''; ?>">
	<div class="efa-auth-split">
		<?php if ( $show_panel ) : ?>
			<aside class="efa-auth-panel" aria-label="<?php esc_attr_e( 'What you get with a free account', 'english-finders-account' ); ?>">
				<p class="efa-auth-panel__eyebrow"><?php esc_html_e( 'English Finders', 'english-finders-account' ); ?></p>
				<p class="efa-auth-panel__title">
					<?php echo $is_login ? esc_html__( 'Pick up where you left off.', 'english-finders-account' ) : esc_html__( 'Learn English at your level.', 'english-finders-account' ); ?>
				</p>
				<ul class="efa-auth-panel__list">
					<li>
						<?php echo Icons::svg( 'chart', 'efa-auth-panel__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?>
						<span><strong><?php esc_html_e( 'Free level test', 'english-finders-account' ); ?></strong> <?php esc_html_e( 'Find your CEFR level, A1 to C2, and the course that fits.', 'english-finders-account' ); ?></span>
					</li>
					<?php if ( $facts['courses'] > 0 && $facts['lessons'] > 0 ) : ?>
						<li>
							<?php echo Icons::svg( 'award', 'efa-auth-panel__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<strong>
									<?php
									printf(
										/* translators: 1: number of courses, 2: number of lessons */
										esc_html__( '%1$s courses, %2$s lessons', 'english-finders-account' ),
										esc_html( number_format_i18n( $facts['courses'] ) ),
										esc_html( AuthView::round_down( $facts['lessons'] ) )
									);
									?>
								</strong>
								<?php esc_html_e( 'From beginner to proficiency, with a certificate for each course you finish.', 'english-finders-account' ); ?>
							</span>
						</li>
					<?php endif; ?>
					<li>
						<?php echo Icons::svg( 'bolt', 'efa-auth-panel__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><strong><?php esc_html_e( 'Practice that remembers', 'english-finders-account' ); ?></strong> <?php esc_html_e( 'Quizzes, games and word tools. Your mistakes are saved so you can review them.', 'english-finders-account' ); ?></span>
					</li>
					<?php if ( $facts['words'] > 0 ) : ?>
						<li>
							<?php echo Icons::svg( 'bookmark', 'efa-auth-panel__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<strong>
									<?php
									/* translators: %s: number of dictionary words, e.g. 64,000+ */
									printf( esc_html__( '%s-word dictionary', 'english-finders-account' ), esc_html( AuthView::round_down( $facts['words'] ) ) );
									?>
								</strong>
								<?php esc_html_e( 'Look up meanings, CEFR levels and pronunciation.', 'english-finders-account' ); ?>
							</span>
						</li>
					<?php endif; ?>
				</ul>
				<p class="efa-auth-panel__foot"><?php esc_html_e( 'Free to join. No card needed.', 'english-finders-account' ); ?></p>
			</aside>
		<?php endif; ?>

		<section class="efa-auth-card efa-auth-main">
			<?php if ( $logged_in ) : ?>
				<h1 class="efa-auth-title"><?php esc_html_e( "You're logged in", 'english-finders-account' ); ?></h1>
				<p class="efa-auth-switch">
					<?php
					/* translators: %s: display name */
					printf( esc_html__( 'Signed in as %s. Visitors who are logged in skip this page and go straight to My Account.', 'english-finders-account' ), '<strong>' . esc_html( $display_name ) . '</strong>' );
					?>
				</p>
				<a class="efa-auth-submit efa-auth-submit--link" href="<?php echo esc_url( Urls::my_account() ); ?>"><?php esc_html_e( 'Go to My Account', 'english-finders-account' ); ?></a>
			<?php else : ?>
				<h1 class="efa-auth-title"><?php echo $is_login ? esc_html__( 'Welcome back', 'english-finders-account' ) : esc_html__( 'Create your free account', 'english-finders-account' ); ?></h1>
				<p class="efa-auth-switch">
					<?php if ( $is_login ) : ?>
						<?php esc_html_e( 'New to English Finders?', 'english-finders-account' ); ?>
						<a href="<?php echo esc_url( Urls::signup_page( $return ) ); ?>"><?php esc_html_e( 'Create a free account', 'english-finders-account' ); ?> <span aria-hidden="true">→</span></a>
					<?php else : ?>
						<?php esc_html_e( 'Already have an account?', 'english-finders-account' ); ?>
						<a href="<?php echo esc_url( Urls::login_page( $return ) ); ?>"><?php esc_html_e( 'Log in', 'english-finders-account' ); ?> <span aria-hidden="true">→</span></a>
					<?php endif; ?>
				</p>

				<?php if ( '' !== $return ) : ?>
					<div class="efa-notice efa-notice-info"><p><?php esc_html_e( 'You will go straight back to where you were.', 'english-finders-account' ); ?></p></div>
				<?php endif; ?>
				<?php if ( $show_error ) : ?>
					<div class="efa-notice efa-notice-error" role="alert"><p><?php echo esc_html( $error_message ); ?></p></div>
				<?php endif; ?>

				<?php
				if ( $is_login ) {
					include EFA_PATH . 'templates/public/partials/login-form.php';
				} else {
					include EFA_PATH . 'templates/public/partials/signup-form.php';
				}
				?>
			<?php endif; ?>
		</section>
	</div>
</div>
