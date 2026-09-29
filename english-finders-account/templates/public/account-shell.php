<?php
/**
 * Logged-in /my-account/ view: progress, library, profile, membership,
 * privacy, data requests.
 *
 * Shows only what's real rather than stubbing "coming soon" panels, the
 * same honesty principle Phase A1 established. Course certificates (A6)
 * arrived in 0.11.0. My Level (0.7.0, Phase A4)
 * comes first, since "where am I" is the first question the page answers.
 *
 * 0.6.0 visual pass: a profile header (avatar, member-since, plan badge)
 * and a left-nav dashboard layout, replacing the single scrolling page
 * this template rendered through 0.5.0 -- closing the gap this page had
 * against Duolingo's/7ESL's dashboard-with-navigation feel. Still one
 * server-rendered page, no client routing: nav links are plain anchors to
 * each section's id, no JS.
 *
 * @package EnglishFindersAccount
 *
 * @var WP_User               $user
 * @var array<string,mixed>   $profile
 * @var array<string,mixed>|null $membership Phase A5 data, or null when Core's billing services aren't available/new enough.
 * @var array<string,mixed>|null $progress   Phase A3 data, or null when Core's activity service isn't available/new enough.
 * @var array<string,mixed>|null $library    Phase A3 data, or null when WGP's Support\Api facade isn't available/new enough.
 * @var array<string,mixed>|null $level      Phase A4 data, or null when Core's level_results service isn't available/new enough.
 * @var array<string,mixed>|null $mistakes   Mistake notebook data, or null when Core's mistakes service isn't available (Core < 1.9.0).
 * @var array<string,mixed>|null $leaderboard Weekly leaderboard data, or null when Core is older than 1.11.0.
 * @var array<string,mixed>|null $certificates Course certificates, or null when Core is older than 1.12.0.
 * @var array<string,mixed>|null $home        "Continue" card + suggested next step (0.12.0), or null when there is nothing to show.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag, not a state change.
$notice = isset( $_GET['efa_notice'] ) ? sanitize_key( wp_unslash( $_GET['efa_notice'] ) ) : '';

/** First letters of up to the first two words of the display name, for the avatar circle -- no photo upload exists yet, and this beats a blank placeholder. */
$initials = static function ( string $name ): string {
	$words    = array_values( array_filter( explode( ' ', trim( $name ) ) ) );
	$initials = '';
	foreach ( array_slice( $words, 0, 2 ) as $word ) {
		$initials .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
	}

	return '' !== $initials ? $initials : '?';
};

/* 0.13.0: profile + password errors, shown as a red notice at the top. */
$efa_error_notices = array(
	'name_invalid'      => __( 'Please enter your full name (at least 2 letters). Nothing was changed.', 'english-finders-account' ),
	'avatar_invalid'    => __( 'The profile photo must be a JPG or PNG image. Your other changes were saved.', 'english-finders-account' ),
	'avatar_too_big'    => __( 'The profile photo must be 2 MB or smaller. Your other changes were saved.', 'english-finders-account' ),
	'avatar_too_small'  => __( 'The profile photo is too small. Please use one at least 64 pixels wide and tall. Your other changes were saved.', 'english-finders-account' ),
	'avatar_unreadable' => __( 'That photo could not be processed. Please try a different JPG or PNG. Your other changes were saved.', 'english-finders-account' ),
	'password_wrong'    => __( 'Your current password was not right. Your password was not changed.', 'english-finders-account' ),
	'password_short'    => __( 'The new password must be at least 8 characters.', 'english-finders-account' ),
	'password_mismatch' => __( "The two new passwords don't match.", 'english-finders-account' ),
	'password_failed'   => __( 'Something went wrong changing your password. Please try again.', 'english-finders-account' ),
);

$efa_avatar_url = ( new \EnglishFindersAccount\Profile\Avatar() )->url( (int) $user->ID );

/* Mirrors membership-section.php's own $plan_labels map -- kept as a small local duplicate rather than a shared helper, since this is two static translated strings, not business logic. */
$plan_labels = array(
	'pro'         => __( 'Pro', 'english-finders-account' ),
	'teacher_pro' => __( 'Teacher Pro', 'english-finders-account' ),
);
?>
<div class="efa-account-shell">
	<?php if ( 'saved' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Saved.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'export_requested' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Check your email to confirm the data export request.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'erase_requested' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Check your email to confirm the account deletion request.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'request_failed' === $notice ) : ?>
		<div class="efa-notice efa-notice-error"><p><?php esc_html_e( 'Something went wrong submitting that request. Please try again.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'pro_welcome' === $notice ) : ?>
		<?php // 0.18.0: after Paddle's checkout. Paddle confirms the payment to the site a few seconds later. ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Thank you for going Pro! Your membership is being activated; it usually takes a few seconds. If this page still says Free, refresh it in a moment.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'leaderboard_joined' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'You joined the weekly leaderboard.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'leaderboard_left' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'You left the leaderboard. Your name no longer appears on it.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'password_changed' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Password changed. Other devices signed in to your account have been signed out.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( isset( $efa_error_notices[ $notice ] ) ) : ?>
		<div class="efa-notice efa-notice-error"><p><?php echo esc_html( $efa_error_notices[ $notice ] ); ?></p></div>
	<?php elseif ( 'mistake_resolved' === $notice ) : ?>
		<div class="efa-notice efa-notice-success"><p><?php esc_html_e( 'Marked as learned.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'billing_unavailable' === $notice ) : ?>
		<div class="efa-notice efa-notice-error"><p><?php esc_html_e( 'Billing isn\'t set up yet.', 'english-finders-account' ); ?></p></div>
	<?php elseif ( 'portal_error' === $notice ) : ?>
		<div class="efa-notice efa-notice-error"><p><?php esc_html_e( 'Could not open the billing portal right now. Please try again shortly.', 'english-finders-account' ); ?></p></div>
	<?php endif; ?>

	<div class="efa-account-header">
		<?php if ( '' !== $efa_avatar_url ) : ?>
			<img class="efa-account-avatar efa-account-avatar--photo" src="<?php echo esc_url( $efa_avatar_url ); ?>" alt="" width="64" height="64" referrerpolicy="no-referrer">
		<?php else : ?>
			<div class="efa-account-avatar" aria-hidden="true"><?php echo esc_html( $initials( $user->display_name ) ); ?></div>
		<?php endif; ?>
		<div class="efa-account-header__info">
			<h1>
				<?php
				printf(
					/* translators: %s: display name */
					esc_html__( 'Hi, %s', 'english-finders-account' ),
					esc_html( $user->display_name )
				);
				?>
			</h1>
			<p class="efa-account-header__meta">
				<?php
				printf(
					/* translators: %s: date the account was created */
					esc_html__( 'Member since %s', 'english-finders-account' ),
					esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) )
				);
				?>
				<?php if ( \EnglishFindersAccount\Profile\AccountType::TEACHER === $profile['account_type'] ) : ?>
					<span class="efa-plan-badge"><?php esc_html_e( 'Teacher', 'english-finders-account' ); ?></span>
				<?php endif; ?>
				<?php if ( null !== $membership ) : ?>
					<span class="efa-plan-badge">
						<?php echo esc_html( $plan_labels[ $membership['entitlement']->plan_code ] ?? ucfirst( $membership['entitlement']->plan_code ) ); ?>
					</span>
				<?php endif; ?>
			</p>
		</div>
	</div>

	<div class="efa-account-layout">
		<nav class="efa-account-nav" aria-label="<?php esc_attr_e( 'Account sections', 'english-finders-account' ); ?>">
			<?php if ( null !== $level ) : ?>
				<a href="#efa-section-level"><?php esc_html_e( 'My level', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<?php if ( null !== $progress ) : ?>
				<a href="#efa-section-progress"><?php esc_html_e( 'Progress', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<?php if ( null !== $certificates ) : ?>
				<a href="#efa-section-certificates"><?php esc_html_e( 'Certificates', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<?php if ( null !== $leaderboard ) : ?>
				<a href="#efa-section-leaderboard"><?php esc_html_e( 'Leaderboard', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<?php if ( null !== $library ) : ?>
				<a href="#efa-section-library"><?php esc_html_e( 'Library', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<?php if ( null !== $mistakes ) : ?>
				<a href="#efa-section-mistakes"><?php esc_html_e( 'Mistakes', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<a href="#efa-section-profile"><?php esc_html_e( 'Profile', 'english-finders-account' ); ?></a>
			<a href="#efa-section-password"><?php esc_html_e( 'Password', 'english-finders-account' ); ?></a>
			<?php if ( null !== $membership ) : ?>
				<a href="#efa-section-membership"><?php esc_html_e( 'Membership', 'english-finders-account' ); ?></a>
			<?php endif; ?>
			<a href="#efa-section-data"><?php esc_html_e( 'Your data', 'english-finders-account' ); ?></a>
		</nav>

		<div class="efa-account-main">
			<?php include EFA_PATH . 'templates/public/home-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/level-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/progress-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/certificates-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/leaderboard-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/library-section.php'; ?>

			<?php include EFA_PATH . 'templates/public/mistakes-section.php'; ?>

			<section class="efa-account-section" id="efa-section-profile">
				<h2><?php esc_html_e( 'Profile', 'english-finders-account' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="efa_save_profile">
					<?php wp_nonce_field( 'efa_save_profile' ); ?>
					<div class="efa-photo-field" data-efa-photo-picker>
						<?php if ( '' !== $efa_avatar_url ) : ?>
							<div class="efa-photo-field__preview has-photo" data-efa-photo-preview><img src="<?php echo esc_url( $efa_avatar_url ); ?>" alt="" width="72" height="72" referrerpolicy="no-referrer"></div>
						<?php else : ?>
							<div class="efa-photo-field__preview efa-photo-field__preview--empty" data-efa-photo-preview aria-hidden="true"><?php echo esc_html( $initials( $user->display_name ) ); ?></div>
						<?php endif; ?>
						<div class="efa-photo-field__controls">
							<label for="efa-avatar"><?php esc_html_e( 'Profile photo', 'english-finders-account' ); ?></label>
							<input type="file" id="efa-avatar" name="avatar" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
							<span class="efa-field-hint"><?php esc_html_e( 'JPG or PNG. Cropped to a square; large photos are made smaller before upload.', 'english-finders-account' ); ?></span>
							<span class="efa-photo-field__status" data-efa-photo-status aria-live="polite"></span>
							<?php if ( '' !== $efa_avatar_url ) : ?>
								<label class="efa-photo-field__remove"><input type="checkbox" name="remove_avatar" value="1"> <?php esc_html_e( 'Remove my photo', 'english-finders-account' ); ?></label>
							<?php endif; ?>
						</div>
					</div>
					<p>
						<label for="efa-display-name"><?php esc_html_e( 'Full name', 'english-finders-account' ); ?></label>
						<input type="text" id="efa-display-name" name="display_name" required minlength="2" maxlength="80" autocomplete="name" value="<?php echo esc_attr( $user->display_name ); ?>">
						<span class="efa-field-hint"><?php esc_html_e( 'Shown on your certificates and, if you join, the leaderboard.', 'english-finders-account' ); ?></span>
					</p>
					<p>
						<label for="efa-account-type"><?php esc_html_e( 'I use English Finders as', 'english-finders-account' ); ?></label>
						<select id="efa-account-type" name="account_type">
							<option value="learner" <?php selected( (string) $profile['account_type'], 'learner' ); ?>><?php esc_html_e( 'A learner', 'english-finders-account' ); ?></option>
							<option value="teacher" <?php selected( (string) $profile['account_type'], 'teacher' ); ?>><?php esc_html_e( 'A teacher', 'english-finders-account' ); ?></option>
						</select>
					</p>
					<p>
						<label for="efa-native-language"><?php esc_html_e( 'Native language', 'english-finders-account' ); ?></label>
						<input type="text" id="efa-native-language" name="native_language" value="<?php echo esc_attr( (string) $profile['native_language'] ); ?>">
					</p>
					<?php if ( class_exists( '\\EnglishFindersCore\\Activity\\DailyGoal' ) ) : ?>
						<p>
							<label for="efa-daily-goal"><?php esc_html_e( 'Daily goal', 'english-finders-account' ); ?></label>
							<select id="efa-daily-goal" name="daily_goal">
								<?php foreach ( \EnglishFindersCore\Activity\DailyGoal::TIERS as $efa_tier => $efa_xp ) : ?>
									<option value="<?php echo esc_attr( $efa_tier ); ?>" <?php selected( (string) $profile['daily_goal'], $efa_tier ); ?>>
										<?php
										printf(
											/* translators: 1: goal name, e.g. "Regular", 2: XP per day */
											esc_html__( '%1$s — %2$d XP a day', 'english-finders-account' ),
											esc_html( \EnglishFindersCore\Activity\DailyGoal::label( $efa_tier ) ),
											(int) $efa_xp
										);
										?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="efa-field-hint"><?php esc_html_e( '1 XP per correct answer in practice, quizzes and games, 5 per solved puzzle, 20 per lesson.', 'english-finders-account' ); ?></span>
						</p>
					<?php endif; ?>

					<h3><?php esc_html_e( 'Privacy', 'english-finders-account' ); ?></h3>
					<p>
						<label>
							<input type="checkbox" name="public_profile" value="1" <?php checked( (bool) $profile['public_profile'] ); ?>>
							<?php esc_html_e( 'Make my profile public', 'english-finders-account' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" name="leaderboard_optin" value="1" <?php checked( (bool) $profile['leaderboard_optin'] ); ?>>
							<?php esc_html_e( 'Show me on the weekly leaderboard', 'english-finders-account' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="checkbox" name="notifications_enabled" value="1" <?php checked( (bool) $profile['notifications_enabled'] ); ?>>
							<?php esc_html_e( 'Email me notifications', 'english-finders-account' ); ?>
						</label>
					</p>

					<button type="submit"><?php esc_html_e( 'Save changes', 'english-finders-account' ); ?></button>
				</form>
			</section>

			<?php $efa_needs_current = \EnglishFindersAccount\Auth\PasswordChangeHandler::needs_current_password( (int) $user->ID ); ?>
			<section class="efa-account-section" id="efa-section-password">
				<h2><?php echo $efa_needs_current ? esc_html__( 'Change password', 'english-finders-account' ) : esc_html__( 'Set a password', 'english-finders-account' ); ?></h2>
				<?php if ( ! $efa_needs_current ) : ?>
					<p class="description"><?php esc_html_e( 'You sign in with Google. Set a password if you also want to log in with your email address.', 'english-finders-account' ); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="efa-password-form">
					<input type="hidden" name="action" value="<?php echo esc_attr( \EnglishFindersAccount\Auth\PasswordChangeHandler::ACTION ); ?>">
					<?php wp_nonce_field( \EnglishFindersAccount\Auth\PasswordChangeHandler::ACTION ); ?>
					<?php /* Lets password managers attach the new password to the right account. */ ?>
					<input type="text" name="username" value="<?php echo esc_attr( $user->user_email ); ?>" autocomplete="username" hidden>
					<?php if ( $efa_needs_current ) : ?>
						<p>
							<label for="efa-current-password"><?php esc_html_e( 'Current password', 'english-finders-account' ); ?></label>
							<span class="efa-pw-field" data-efa-password>
								<input type="password" id="efa-current-password" name="current_password" required autocomplete="current-password">
								<button type="button" class="efa-pw-toggle" aria-controls="efa-current-password" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
							</span>
						</p>
					<?php endif; ?>
					<p>
						<label for="efa-new-password"><?php esc_html_e( 'New password', 'english-finders-account' ); ?></label>
						<span class="efa-pw-field" data-efa-password>
							<input type="password" id="efa-new-password" name="new_password" required minlength="8" autocomplete="new-password">
							<button type="button" class="efa-pw-toggle" aria-controls="efa-new-password" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
						</span>
						<span class="efa-field-hint"><?php esc_html_e( 'At least 8 characters.', 'english-finders-account' ); ?></span>
					</p>
					<p>
						<label for="efa-confirm-password"><?php esc_html_e( 'Confirm new password', 'english-finders-account' ); ?></label>
						<span class="efa-pw-field" data-efa-password>
							<input type="password" id="efa-confirm-password" name="confirm_password" required minlength="8" autocomplete="new-password" data-efa-match="efa-new-password" aria-describedby="efa-confirm-password-match">
							<button type="button" class="efa-pw-toggle" aria-controls="efa-confirm-password" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'english-finders-account' ); ?></button>
						</span>
						<span class="efa-pw-match" id="efa-confirm-password-match" data-efa-match-status aria-live="polite"></span>
					</p>
					<button type="submit"><?php echo $efa_needs_current ? esc_html__( 'Change password', 'english-finders-account' ) : esc_html__( 'Set password', 'english-finders-account' ); ?></button>
				</form>
				<?php if ( $efa_needs_current ) : ?>
					<p class="efa-auth-meta"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your current password?', 'english-finders-account' ); ?></a></p>
				<?php endif; ?>
			</section>

			<?php include EFA_PATH . 'templates/public/membership-section.php'; ?>

			<section class="efa-account-section" id="efa-section-data">
				<h2><?php esc_html_e( 'Your data', 'english-finders-account' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Both buttons send a confirmation link to your email first. Nothing happens until you click that link.', 'english-finders-account' ); ?></p>
				<div class="efa-data-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="efa_request_export">
						<?php wp_nonce_field( 'efa_request_export' ); ?>
						<button type="submit"><?php esc_html_e( 'Export my data', 'english-finders-account' ); ?></button>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						onsubmit="return confirm('<?php echo esc_js( __( 'Request permanent deletion of your account and data? You will need to confirm this by email.', 'english-finders-account' ) ); ?>');">
						<input type="hidden" name="action" value="efa_request_erase">
						<?php wp_nonce_field( 'efa_request_erase' ); ?>
						<button type="submit" class="efa-button-danger"><?php esc_html_e( 'Delete my account', 'english-finders-account' ); ?></button>
					</form>
				</div>
			</section>

			<p class="efa-logout"><a class="efa-logout__button" href="<?php echo esc_url( wp_logout_url( home_url( '/my-account/' ) ) ); ?>"><?php esc_html_e( 'Log out', 'english-finders-account' ); ?></a></p>
		</div>
	</div>
</div>
