<?php
/**
 * Header account links (0.14.0) -- rendered by [efa_account_links].
 *
 * Guests: "Log in" + a "Sign up" button (both return to the current page
 * afterwards). Members: their photo (or initial) + "My account". Unlike a
 * fixed header button, "Sign up" never shows to someone already logged in.
 * Styles are printed once, inline, because this sits in the site header on
 * every page, where the account stylesheet isn't loaded. data-no-optimize
 * keeps LiteSpeed from moving them into its combined / "unused CSS" files,
 * which are built per page ahead of time and loaded async (0.14.1).
 *
 * 0.14.2: the row is a block-level flex box, so Astra centres it like its
 * other header items (as inline-flex it sat on the text baseline and the
 * avatar row rode ~3px high). "Log in" uses the menu's own font, and
 * "My account" is an outlined button matching "Sign up".
 *
 * @package EnglishFindersAccount
 *
 * @var array<string,string> $atts
 * @var bool   $print_style
 * @var int    $user_id
 * @var string $avatar
 * @var string $name
 * @var string $login_url
 * @var string $signup_url
 * @var string $show 'both' | 'login' | 'signup'
 * @var string $menu_vars CSS custom properties for the menu font ('' when unknown)
 * @var bool   $is_pro     0.19.0: show the PRO badge
 * @var string $go_pro_url 0.19.0: "Go Pro" link for a free member ('' = none)
 * @var int    $header_break 0.19.0: the theme's mobile-header breakpoint (Astra: 768 on this site)
 *
 * 0.19.0 layout (measured in the live Astra header, 2026-09-26): the header
 * row is full between the theme's breakpoint and ~1250px (the menu wraps
 * with even a 32px extra item), so "Go Pro" is hidden there; full button
 * from 1260px and on tablets; a star-only circle on phones (text kept for
 * screen readers). The PRO badge sits over the avatar (a gold ring + tag),
 * so the My account button never gets wider.
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $print_style ) : ?>
<style id="efa-account-links-css" data-no-optimize="1">
<?php if ( '' !== $menu_vars ) : ?>
.efa-links{<?php echo $menu_vars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted in AuthPages::menu_typography(). ?>}
<?php endif; ?>
.efa-links{display:flex;align-items:center;gap:14px;margin:0;font-family:Lexend,sans-serif;line-height:1.2}
.efa-links a{box-sizing:border-box;text-decoration:none}
.efa-links a.efa-links__login{display:inline-block;padding:8px 4px;color:#075aae;font-family:var(--efa-menu-font,inherit);font-size:var(--efa-menu-size,16px);font-weight:var(--efa-menu-weight,500);line-height:1.2}
.efa-links a.efa-links__login:hover{color:#06498c;text-decoration:underline}
.efa-links a.efa-links__signup{display:inline-flex;align-items:center;height:38px;padding:0 20px;border-radius:25px;background:#075aae;color:#fff;font-size:15px;font-weight:500;line-height:1;white-space:nowrap}
.efa-links a.efa-links__signup:hover{background:#067ae0;color:#fff}
.efa-links a.efa-links__account{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px 0 3px;border:1.5px solid #075aae;border-radius:25px;background:#fff;color:#075aae;font-size:15px;font-weight:500;line-height:1;white-space:nowrap;transition:background-color .15s,color .15s}
.efa-links a.efa-links__account:hover{background:#075aae;color:#fff}
.efa-links a.efa-links__account:hover .efa-links__avatar{box-shadow:0 0 0 1.5px #fff}
.efa-links a:focus-visible{outline:2px solid #075aae;outline-offset:2px}
.efa-links__avatar{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:50%;overflow:hidden;background:#075aae;color:#fff;font-size:13px;font-weight:600;flex:none}
.efa-links__avatar img{width:100%;height:100%;object-fit:cover;display:block}
.efa-links a.efa-links__gopro{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:34px;padding:0 14px;border-radius:25px;background:#fff7e0;border:1.5px solid #e0a800;color:#7a5200;font-size:14px;font-weight:600;line-height:1;white-space:nowrap}
.efa-links a.efa-links__gopro:hover{background:#ffe9a8;color:#5c3d00}
.efa-links a.efa-links__account.is-pro{position:relative}
.efa-links a.efa-links__account.is-pro .efa-links__avatar{box-shadow:0 0 0 2px #e0a800}
.efa-links__pro{position:absolute;left:0;bottom:-6px;padding:2px 4px;border-radius:6px;background:#e0a800;color:#fff;font-size:9px;font-weight:700;letter-spacing:.04em;line-height:1;pointer-events:none}
@media (min-width:<?php echo (int) $header_break + 1; ?>px) and (max-width:1259px){.efa-links a.efa-links__gopro{display:none}}
@media (max-width:544px){.efa-links a.efa-links__gopro{width:32px;height:32px;padding:0;font-size:15px}.efa-links__gopro-text{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}}
@media (max-width:544px){.efa-links{gap:10px}.efa-links a.efa-links__login{font-size:16px}.efa-links a.efa-links__signup{height:34px;padding:0 14px;font-size:14px}.efa-links a.efa-links__account{height:36px;padding:0 2px}.efa-links__label{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}}
</style>
<?php endif; ?>
<?php if ( $user_id > 0 ) : ?>
	<nav class="efa-links efa-links--member" aria-label="<?php esc_attr_e( 'Your account', 'english-finders-account' ); ?>">
		<?php if ( '' !== $go_pro_url ) : ?>
			<a class="efa-links__gopro" href="<?php echo esc_url( $go_pro_url ); ?>"><span aria-hidden="true">&#9733;</span> <span class="efa-links__gopro-text"><?php esc_html_e( 'Go Pro', 'english-finders-account' ); ?></span></a>
		<?php endif; ?>
		<a class="efa-links__account<?php echo $is_pro ? ' is-pro' : ''; ?>" href="<?php echo esc_url( Urls::my_account() ); ?>">
			<span class="efa-links__avatar" aria-hidden="true">
				<?php if ( '' !== $avatar ) : ?>
					<img src="<?php echo esc_url( $avatar ); ?>" alt="" width="34" height="34" referrerpolicy="no-referrer">
				<?php else : ?>
					<?php echo esc_html( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( trim( $name ), 0, 1 ) ) : strtoupper( substr( trim( $name ), 0, 1 ) ) ); ?>
				<?php endif; ?>
			</span>
			<span class="efa-links__label"><?php echo esc_html( $atts['account_text'] ); ?></span>
			<?php if ( $is_pro ) : ?>
				<span class="efa-links__pro"><?php esc_html_e( 'PRO', 'english-finders-account' ); ?></span>
			<?php endif; ?>
		</a>
	</nav>
<?php else : ?>
	<nav class="efa-links efa-links--guest" aria-label="<?php esc_attr_e( 'Log in or sign up', 'english-finders-account' ); ?>">
		<?php if ( 'signup' !== $show ) : ?>
			<a class="efa-links__login" rel="nofollow" href="<?php echo esc_url( $login_url ); ?>"><?php echo esc_html( $atts['login_text'] ); ?></a>
		<?php endif; ?>
		<?php if ( 'login' !== $show ) : ?>
			<a class="efa-links__signup" rel="nofollow" href="<?php echo esc_url( $signup_url ); ?>"><?php echo esc_html( $atts['signup_text'] ); ?></a>
		<?php endif; ?>
	</nav>
<?php endif; ?>
