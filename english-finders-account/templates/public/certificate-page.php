<?php
/**
 * Standalone certificate / verification page (0.11.0).
 *
 * Rendered by CertificatePage outside the theme so it prints as a single
 * landscape A4 page. Colours and type are the site's captured brand tokens
 * (Lexend, #075AAE accent, #0E2A4A ink) -- the same ones account.css uses.
 *
 * @package EnglishFindersAccount
 *
 * @var array{code:string,course_title:string,level:string,learner_name:string,issued_at:string,url:string}|null $data
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$efa_site = get_bloginfo( 'name' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( null !== $data ? sprintf( /* translators: 1: learner name, 2: course title */ __( 'Certificate: %1$s – %2$s', 'english-finders-account' ), $data['learner_name'], $data['course_title'] ) : __( 'Certificate not found', 'english-finders-account' ) ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600&display=swap">
	<style>
		:root { --ink: #0e2a4a; --accent: #075aae; --muted: #6b7280; --line: #d9e3ef; --bg: #f5f5f5; }
		* { box-sizing: border-box; }
		body { margin: 0; background: var(--bg); font-family: Lexend, system-ui, sans-serif; color: #1a1a1a; }
		.bar { max-width: 1000px; margin: 24px auto 12px; padding: 0 16px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
		.bar a, .bar button { font: inherit; font-size: 14px; border-radius: 5px; padding: 9px 16px; cursor: pointer; text-decoration: none; }
		.bar button { border: 0; background: var(--accent); color: #fff; }
		.bar a { color: var(--accent); border: 1px solid var(--line); background: #fff; }
		.sheet { max-width: 1000px; margin: 0 auto 32px; padding: 0 16px; }
		.cert { aspect-ratio: 297 / 210; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 3.5%; position: relative; box-shadow: 0 1px 2px rgba(16,24,40,.04); }
		.cert__frame { height: 100%; border: 2px solid var(--accent); border-radius: 10px; padding: 5% 7%; display: flex; flex-direction: column; align-items: center; text-align: center; }
		.brand { font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: var(--accent); font-size: clamp(11px, 1.4vw, 15px); }
		.title { margin: 3% 0 0; font-weight: 500; color: var(--ink); font-size: clamp(22px, 4vw, 44px); line-height: 1.1; }
		.lead { margin: 4% 0 1%; color: var(--muted); font-size: clamp(12px, 1.5vw, 16px); }
		.name { margin: 0; font-weight: 600; color: var(--ink); font-size: clamp(24px, 4.6vw, 52px); line-height: 1.15; border-bottom: 1px solid var(--line); padding: 0 4% 1.5%; }
		.course { margin: 1.5% 0 0; font-weight: 500; color: var(--ink); font-size: clamp(15px, 2.2vw, 24px); }
		.level { margin-top: 2.5%; display: inline-flex; align-items: center; gap: 10px; font-size: clamp(11px, 1.3vw, 14px); color: var(--muted); }
		.level b { display: inline-grid; place-items: center; min-width: 2.6em; height: 2.6em; padding: 0 .5em; border-radius: 10px; background: var(--accent); color: #fff; font-size: 1.25em; }
		.meta { margin-top: auto; width: 100%; display: flex; justify-content: space-between; gap: 16px; font-size: clamp(10px, 1.2vw, 13px); color: var(--muted); text-align: left; }
		.meta strong { display: block; color: var(--ink); font-weight: 500; font-size: 1.1em; }
		.meta .verify { text-align: right; word-break: break-all; }
		.note { max-width: 1000px; margin: 0 auto 40px; padding: 0 16px; font-size: 13px; color: var(--muted); line-height: 1.6; }
		.missing { max-width: 560px; margin: 12vh auto; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 32px; text-align: center; }
		.missing h1 { color: var(--ink); font-weight: 500; }
		.missing a { color: var(--accent); }
		@media (max-width: 640px) {
			.cert { aspect-ratio: auto; }
			.cert__frame { padding: 28px 18px; }
			.meta { flex-direction: column; margin-top: 24px; text-align: center; }
			.meta .verify { text-align: center; }
		}
		@page { size: A4 landscape; margin: 10mm; }
		@media print {
			body { background: #fff; }
			.bar, .note { display: none; }
			.sheet { max-width: none; margin: 0; padding: 0; }
			.cert { box-shadow: none; border: 0; border-radius: 0; height: 186mm; aspect-ratio: auto; padding: 0; break-inside: avoid; }
			.brand { font-size: 13px; } .title { font-size: 40px; } .lead { font-size: 15px; } .name { font-size: 46px; } .course { font-size: 22px; } .level { font-size: 13px; } .meta { font-size: 12px; }
		}
	</style>
</head>
<body>
<?php if ( null === $data ) : ?>
	<div class="missing">
		<h1><?php esc_html_e( 'Certificate not found', 'english-finders-account' ); ?></h1>
		<p><?php esc_html_e( 'There is no English Finders certificate with this code. Please check the link you were given.', 'english-finders-account' ); ?></p>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $efa_site ); ?></a></p>
	</div>
<?php else : ?>
	<div class="bar">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">← <?php echo esc_html( $efa_site ); ?></a>
		<button type="button" onclick="window.print()"><?php esc_html_e( 'Print / save as PDF', 'english-finders-account' ); ?></button>
	</div>
	<main class="sheet">
		<div class="cert">
			<div class="cert__frame">
				<div class="brand"><?php echo esc_html( $efa_site ); ?></div>
				<h1 class="title"><?php esc_html_e( 'Certificate of Completion', 'english-finders-account' ); ?></h1>
				<p class="lead"><?php esc_html_e( 'This certifies that', 'english-finders-account' ); ?></p>
				<p class="name"><?php echo esc_html( $data['learner_name'] ); ?></p>
				<p class="lead"><?php esc_html_e( 'has completed every lesson and quiz of the course', 'english-finders-account' ); ?></p>
				<p class="course"><?php echo esc_html( $data['course_title'] ); ?></p>
				<?php if ( '' !== $data['level'] ) : ?>
					<div class="level">
						<b><?php echo esc_html( $data['level'] ); ?></b>
						<span><?php esc_html_e( 'CEFR level of the course', 'english-finders-account' ); ?></span>
					</div>
				<?php endif; ?>
				<div class="meta">
					<div>
						<?php esc_html_e( 'Date issued', 'english-finders-account' ); ?>
						<strong><?php echo esc_html( mysql2date( 'j F Y', get_date_from_gmt( $data['issued_at'] ) ) ); ?></strong>
					</div>
					<div>
						<?php esc_html_e( 'Certificate ID', 'english-finders-account' ); ?>
						<strong><?php echo esc_html( $data['code'] ); ?></strong>
					</div>
					<div class="verify">
						<?php esc_html_e( 'Verify at', 'english-finders-account' ); ?>
						<strong><?php echo esc_html( preg_replace( '#^https?://#', '', $data['url'] ) ); ?></strong>
					</div>
				</div>
			</div>
		</div>
	</main>
	<p class="note">
		<?php esc_html_e( 'This certificate confirms that the learner completed an English Finders online course. It is not an official qualification or an independent assessment of CEFR level. Anyone can check it is genuine by opening the link above.', 'english-finders-account' ); ?>
	</p>
<?php endif; ?>
</body>
</html>
