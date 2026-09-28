<?php
/**
 * [efa_weekly_top] sidebar card (0.15.0) -- see Leaderboard\WeeklyTopWidget.
 *
 * 0.15.1: ranks 1-3 are metal coins -- gold, platinum, bronze -- with the
 * number stamped in a dark shade of each metal (readable on the light face).
 * Keyed on data-rank, so tied ranks share a metal and rows repainted by the
 * script below get it too.
 *
 * Members: names + photos. Visitors who aren't signed in: ranks + XP with the
 * names hidden, refreshed on load from the public JSON file (the page itself
 * may come from LiteSpeed's cache). Styles and the small script are printed
 * once per page, inline and marked data-no-optimize so LiteSpeed leaves them
 * in place (see account-links.php for why).
 *
 * @package EnglishFindersAccount
 *
 * @var string $title
 * @var int    $limit
 * @var bool   $member
 * @var bool   $joined
 * @var bool   $in_rows
 * @var array{xp:int,rank:?int} $viewer
 * @var list<array<string,mixed>> $rows
 * @var string $span
 * @var string $reset_text
 * @var string $board_url
 * @var string $signup_url
 * @var string $login_url
 * @var string $src         Public JSON URL ('' for members).
 * @var bool   $print_assets
 */

declare(strict_types=1);

use EnglishFindersAccount\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* translators: %d: XP earned this week */
$efa_xp_format = __( '%d XP', 'english-finders-account' );
?>
<?php if ( $print_assets ) : ?>
<style id="efa-weekly-top-css" data-no-optimize="1">
.efa-top{box-sizing:border-box;margin:0 0 24px;border:1px solid #e3e8ef;border-radius:16px;overflow:hidden;background:#fff;color:#0e2a4a;font-family:Lexend,sans-serif;line-height:1.3;box-shadow:0 1px 3px rgba(14,42,74,.06)}
.efa-top *{box-sizing:border-box}
.efa-top__head{display:flex;align-items:center;gap:10px;padding:14px 16px;background:linear-gradient(135deg,#075aae 0%,#0e2a4a 100%);color:#fff}
.efa-top__head .efa-icon{flex:none;width:24px;height:24px}
.efa-top h3.efa-top__title{margin:0;padding:0;border:0;font-family:inherit;font-size:16px;font-weight:600;line-height:1.25;color:#fff}
.efa-top__span{display:block;margin-top:2px;font-size:12px;font-weight:400;color:rgba(255,255,255,.85)}
.efa-top ol.efa-top__list{list-style:none;margin:0;padding:4px 0}
.efa-top li.efa-top__row{display:grid;grid-template-columns:24px 32px minmax(0,1fr) auto;align-items:center;gap:10px;margin:0;padding:8px 16px}
.efa-top li.efa-top__row+li.efa-top__row{border-top:1px solid #f0f3f7}
.efa-top li.efa-top__row.is-viewer{background:#eef5fd}
.efa-top__rank{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;font-size:12px;font-weight:600;color:#5b6b7f}
.efa-top__row[data-rank="1"] .efa-top__rank,.efa-top__row[data-rank="2"] .efa-top__rank,.efa-top__row[data-rank="3"] .efa-top__rank{width:26px;height:26px;margin-left:-1px;font-weight:700;box-shadow:inset 0 1px 1px rgba(255,255,255,.85),inset 0 -2px 3px rgba(0,0,0,.2),0 1px 2px rgba(14,42,74,.25)}
.efa-top__row[data-rank="1"] .efa-top__rank{background:radial-gradient(circle at 32% 28%,#fffbe6 0,#ffe27a 18%,#f2c230 42%,#d99c0b 70%,#9c6a00 100%);border:1px solid #a87500;color:#5b3b00;text-shadow:0 1px 0 rgba(255,244,200,.8)}
.efa-top__row[data-rank="2"] .efa-top__rank{background:radial-gradient(circle at 32% 28%,#fff 0,#f4f7fa 20%,#dde3ea 45%,#b7c1cc 72%,#8793a1 100%);border:1px solid #8c98a6;color:#2c3845;text-shadow:0 1px 0 rgba(255,255,255,.9)}
.efa-top__row[data-rank="3"] .efa-top__rank{background:radial-gradient(circle at 32% 28%,#ffe6cc 0,#f3b27a 20%,#d98a4a 45%,#b0652a 72%,#7a4015 100%);border:1px solid #84471a;color:#3e1d05;text-shadow:0 1px 0 rgba(255,221,190,.7)}
.efa-top__avatar{display:inline-grid;place-items:center;width:32px;height:32px;border-radius:50%;overflow:hidden;background:#e8eef6;color:#075aae;font-size:12px;font-weight:600}
.efa-top__avatar img{display:block;width:100%;height:100%;object-fit:cover}
.efa-top__avatar.is-hidden{background:#eef1f5}
.efa-top__name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px;font-weight:500}
.efa-top__name.is-hidden{display:block;height:10px;border-radius:5px;background:#e3e8ef;width:70%}
.efa-top__row:nth-child(2n) .efa-top__name.is-hidden{width:55%}
.efa-top__row:nth-child(3n) .efa-top__name.is-hidden{width:80%}
.efa-top__you{font-weight:400;color:#5b6b7f}
.efa-top__xp{font-size:13px;font-weight:600;color:#075aae;white-space:nowrap}
.efa-top p.efa-top__empty{margin:0;padding:14px 16px;font-size:14px;color:#5b6b7f}
.efa-top__foot{padding:12px 16px 14px;border-top:1px solid #eef1f5;background:#f8fafc;font-size:13px;color:#5b6b7f}
.efa-top__foot p{margin:0 0 10px;font-size:13px}
.efa-top__foot p:last-child{margin-bottom:0}
.efa-top__cta{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px}
.efa-top a.efa-top__btn{display:inline-flex;align-items:center;height:36px;padding:0 18px;border-radius:25px;background:#075aae;color:#fff;font-size:14px;font-weight:500;line-height:1;text-decoration:none}
.efa-top a.efa-top__btn:hover{background:#067ae0;color:#fff}
.efa-top a.efa-top__link{color:#075aae;font-weight:500;text-decoration:none}
.efa-top a.efa-top__link:hover{text-decoration:underline}
.efa-top a:focus-visible{outline:2px solid #075aae;outline-offset:2px}
.efa-top .efa-sr{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.efa-top [hidden]{display:none!important}
</style>
<?php endif; ?>
<section class="efa-top<?php echo $member ? ' efa-top--member' : ' efa-top--guest'; ?>" aria-label="<?php echo esc_attr( $title ); ?>"
	data-limit="<?php echo esc_attr( (string) $limit ); ?>"
	<?php if ( '' !== $src ) : ?>
		data-efa-top-src="<?php echo esc_url( $src ); ?>"
		data-xp-format="<?php echo esc_attr( $efa_xp_format ); ?>"
		data-reset-tonight="<?php esc_attr_e( 'resets at midnight tonight', 'english-finders-account' ); ?>"
		<?php /* translators: %d: days until the weekly reset (the script fills it in) */ ?>
		data-reset-one="<?php esc_attr_e( 'resets in %d day', 'english-finders-account' ); ?>"
		<?php /* translators: %d: days until the weekly reset (the script fills it in) */ ?>
		data-reset-many="<?php esc_attr_e( 'resets in %d days', 'english-finders-account' ); ?>"
	<?php endif; ?>>
	<div class="efa-top__head">
		<?php echo Icons::svg( 'trophy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?>
		<div>
			<h3 class="efa-top__title"><?php echo esc_html( $title ); ?></h3>
			<span class="efa-top__span" data-efa-top-span><?php echo esc_html( $span . ' · ' . $reset_text ); ?></span>
		</div>
	</div>

	<ol class="efa-top__list"<?php echo array() === $rows ? ' hidden' : ''; ?>>
		<?php foreach ( $rows as $efa_row ) : ?>
			<li class="efa-top__row<?php echo ! empty( $efa_row['is_viewer'] ) ? ' is-viewer' : ''; ?>" data-rank="<?php echo esc_attr( (string) (int) $efa_row['rank'] ); ?>">
				<span class="efa-top__rank"><?php echo esc_html( (string) (int) $efa_row['rank'] ); ?></span>
				<?php if ( $member ) : ?>
					<span class="efa-top__avatar" aria-hidden="true">
						<?php if ( '' !== (string) ( $efa_row['avatar'] ?? '' ) ) : ?>
							<img src="<?php echo esc_url( (string) $efa_row['avatar'] ); ?>" alt="" width="32" height="32" loading="lazy" referrerpolicy="no-referrer">
						<?php else : ?>
							<?php echo esc_html( (string) $efa_row['initials'] ); ?>
						<?php endif; ?>
					</span>
					<span class="efa-top__name">
						<?php echo esc_html( (string) $efa_row['name'] ); ?>
						<?php if ( ! empty( $efa_row['is_viewer'] ) ) : ?>
							<span class="efa-top__you"><?php esc_html_e( '(you)', 'english-finders-account' ); ?></span>
						<?php endif; ?>
					</span>
				<?php else : ?>
					<span class="efa-top__avatar is-hidden" aria-hidden="true"></span>
					<span class="efa-top__name is-hidden"><span class="efa-sr"><?php esc_html_e( 'Name shown to members', 'english-finders-account' ); ?></span></span>
				<?php endif; ?>
				<span class="efa-top__xp"><?php echo esc_html( sprintf( $efa_xp_format, (int) $efa_row['xp'] ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="efa-top__empty"<?php echo array() !== $rows ? ' hidden' : ''; ?>><?php esc_html_e( 'Nobody is on the board yet this week.', 'english-finders-account' ); ?></p>

	<?php if ( '' !== $src ) : ?>
		<template>
			<li class="efa-top__row" data-rank=""><span class="efa-top__rank"></span><span class="efa-top__avatar is-hidden" aria-hidden="true"></span><span class="efa-top__name is-hidden"><span class="efa-sr"><?php esc_html_e( 'Name shown to members', 'english-finders-account' ); ?></span></span><span class="efa-top__xp"></span></li>
		</template>
	<?php endif; ?>

	<div class="efa-top__foot">
		<?php if ( ! $member ) : ?>
			<p><?php esc_html_e( 'Names are shown to signed-in members. Earn XP with lessons, quizzes and games to climb the board.', 'english-finders-account' ); ?></p>
			<p class="efa-top__cta">
				<a class="efa-top__btn" rel="nofollow" href="<?php echo esc_url( $signup_url ); ?>"><?php esc_html_e( 'Sign up free', 'english-finders-account' ); ?></a>
				<a class="efa-top__link" rel="nofollow" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'english-finders-account' ); ?></a>
			</p>
		<?php elseif ( ! $joined ) : ?>
			<p><?php esc_html_e( "You're not on the board yet. It's opt-in: only members who join are shown.", 'english-finders-account' ); ?></p>
			<p><a class="efa-top__link" rel="nofollow" href="<?php echo esc_url( $board_url ); ?>"><?php esc_html_e( 'Join the leaderboard →', 'english-finders-account' ); ?></a></p>
		<?php else : ?>
			<?php if ( ! $in_rows ) : ?>
				<p>
					<?php
					if ( null !== $viewer['rank'] ) {
						/* translators: 1: rank, 2: XP this week */
						printf( esc_html__( 'You: #%1$d · %2$d XP this week', 'english-finders-account' ), (int) $viewer['rank'], (int) $viewer['xp'] );
					} else {
						esc_html_e( 'Earn XP this week to get on the board.', 'english-finders-account' );
					}
					?>
				</p>
			<?php endif; ?>
			<p><a class="efa-top__link" rel="nofollow" href="<?php echo esc_url( $board_url ); ?>"><?php esc_html_e( 'See the full leaderboard →', 'english-finders-account' ); ?></a></p>
		<?php endif; ?>
	</div>
</section>
<?php if ( $print_assets && '' !== $src ) : ?>
<script id="efa-weekly-top-js" data-no-optimize="1">
(function () {
	'use strict';
	var cards = document.querySelectorAll( '[data-efa-top-src]' );
	if ( ! cards.length || ! window.fetch ) {
		return;
	}
	// A new URL each minute: the file changes at most every two minutes, and the site tells browsers to keep files for months.
	fetch( cards[ 0 ].getAttribute( 'data-efa-top-src' ) + '?t=' + Math.floor( Date.now() / 60000 ), { credentials: 'omit' } )
		.then( function ( r ) { return r.ok ? r.json() : null; } )
		.then( function ( d ) {
			if ( ! d || ! d.rows || ! d.week ) {
				return;
			}
			for ( var i = 0; i < cards.length; i++ ) {
				paint( cards[ i ], d );
			}
		} )
		.catch( function () {} );

	function paint( card, d ) {
		var now = Date.now() / 1000;
		var over = now >= d.week.ends; // A new week began and nobody has earned XP since the file was written.
		var limit = parseInt( card.getAttribute( 'data-limit' ), 10 ) || 5;
		var rows = over ? [] : d.rows.slice( 0, limit );
		var list = card.querySelector( '.efa-top__list' );
		var empty = card.querySelector( '.efa-top__empty' );
		var tpl = card.querySelector( 'template' );
		var span = card.querySelector( '[data-efa-top-span]' );
		if ( ! list || ! empty || ! tpl || ! tpl.content ) {
			return;
		}
		while ( list.firstChild ) {
			list.removeChild( list.firstChild );
		}
		for ( var i = 0; i < rows.length; i++ ) {
			var li = tpl.content.firstElementChild.cloneNode( true );
			li.setAttribute( 'data-rank', String( rows[ i ].rank ) );
			li.querySelector( '.efa-top__rank' ).textContent = String( rows[ i ].rank );
			li.querySelector( '.efa-top__xp' ).textContent = ( card.getAttribute( 'data-xp-format' ) || '%d XP' ).replace( '%d', String( rows[ i ].xp ) );
			list.appendChild( li );
		}
		list.hidden = 0 === rows.length;
		empty.hidden = rows.length > 0;
		if ( span ) {
			if ( over ) {
				span.textContent = '';
				return;
			}
			var days = Math.max( 0, Math.floor( ( d.week.ends - now ) / 86400 ) );
			var text = 0 === days ? card.getAttribute( 'data-reset-tonight' ) : ( 1 === days ? card.getAttribute( 'data-reset-one' ) : card.getAttribute( 'data-reset-many' ) );
			span.textContent = d.week.span + ' · ' + String( text || '' ).replace( '%d', String( days ) );
		}
	}
}() );
</script>
<?php endif; ?>
