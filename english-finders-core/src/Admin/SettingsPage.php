<?php
/**
 * Core settings screen.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Admin;

use EnglishFindersCore\Audio\AudioStore;
use EnglishFindersCore\Database\Installer;
use EnglishFindersCore\Support\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsPage {
	private const MENU_SLUG  = 'english-finders-core';
	private const OPTION     = Installer::SETTINGS_OPTION;
	private const CAPABILITY = 'manage_options';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_efc_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_efc_purge_audio', array( $this, 'handle_purge' ) );
		add_action( 'admin_post_efc_test_audio', array( $this, 'handle_test' ) );
		add_action( 'admin_post_efc_cefr_import', array( $this, 'handle_cefr' ) );
		add_action( 'admin_post_efc_level_preview', array( $this, 'handle_level_preview' ) );
	}

	public function add_menu(): void {
		add_options_page(
			__( 'English Finders Core', 'english-finders-core' ),
			__( 'English Finders Core', 'english-finders-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/** @return array<string,mixed> */
	private function settings(): array {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Whether the key comes from wp-config.php rather than the database.
	 *
	 * When it does, the field is shown read-only: silently accepting a value
	 * that the constant would override is worse than refusing to take one.
	 */
	private function key_is_constant(): bool {
		return defined( 'EFC_OPENROUTER_KEY' ) && is_string( EFC_OPENROUTER_KEY ) && '' !== trim( EFC_OPENROUTER_KEY );
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'english-finders-core' ) );
		}

		$settings   = $this->settings();
		$store      = new AudioStore();
		$file_count = $store->count();
		$bytes      = $store->usage_bytes();
		$has_key    = $this->key_is_constant() || '' !== trim( (string) ( $settings['openrouter_key'] ?? '' ) );
		$enabled    = ! empty( $settings['tts_enabled'] );
		$notice     = isset( $_GET['efc_notice'] ) ? sanitize_key( wp_unslash( $_GET['efc_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'English Finders Core', 'english-finders-core' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'english-finders-core' ); ?></p></div>
			<?php elseif ( 'previewed' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Preview complete. Nothing was written.', 'english-finders-core' ); ?></p></div>
			<?php elseif ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'CEFR levels imported.', 'english-finders-core' ); ?></p></div>
			<?php elseif ( 'purged' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Generated audio files were deleted. They will be regenerated as words are next played.', 'english-finders-core' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $enabled && ! $has_key ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Audio generation is switched on, but no API key is set, so nothing will be generated.', 'english-finders-core' ); ?></p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Audio pronunciation', 'english-finders-core' ); ?></h2>
			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'When a word has no working recorded pronunciation, speech can be generated instead. Each word is generated once, saved as a file, and reused from then on — so a word is only ever paid for once, and repeat plays cost nothing.', 'english-finders-core' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="efc_save_settings">
				<?php wp_nonce_field( 'efc_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Generated audio', 'english-finders-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="tts_enabled" value="1" <?php checked( $enabled ); ?>>
								<?php esc_html_e( 'Generate speech when no recording is available', 'english-finders-core' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Off by default. Turning this on allows the site to make paid requests to the speech provider.', 'english-finders-core' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="efc-key"><?php esc_html_e( 'OpenRouter API key', 'english-finders-core' ); ?></label></th>
						<td>
							<?php if ( $this->key_is_constant() ) : ?>
								<input type="text" id="efc-key" class="regular-text" value="<?php esc_attr_e( 'Set in wp-config.php', 'english-finders-core' ); ?>" disabled>
								<p class="description"><?php esc_html_e( 'The key is defined as EFC_OPENROUTER_KEY in wp-config.php, which takes precedence over anything stored here. This is the more secure option — the key stays out of the database.', 'english-finders-core' ); ?></p>
							<?php else : ?>
								<input type="password" id="efc-key" name="openrouter_key" class="regular-text" autocomplete="off"
									value="" placeholder="<?php echo $has_key ? esc_attr__( 'A key is saved — leave blank to keep it', 'english-finders-core' ) : 'sk-or-...'; ?>">
								<p class="description">
									<?php esc_html_e( 'Leave blank to keep the existing key. The saved key is never displayed again once entered.', 'english-finders-core' ); ?>
									<br>
									<?php esc_html_e( 'More secure alternative: add this line to wp-config.php instead, and the field above will be ignored.', 'english-finders-core' ); ?>
									<br><code>define( 'EFC_OPENROUTER_KEY', 'sk-or-...' );</code>
								</p>
							<?php endif; ?>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="efc-voice"><?php esc_html_e( 'Voice', 'english-finders-core' ); ?></label></th>
						<td>
							<?php $voice = (string) ( $settings['tts_voice'] ?? 'af_bella' ); ?>
							<input type="text" id="efc-voice" name="tts_voice" class="regular-text" value="<?php echo esc_attr( $voice ); ?>" list="efc-voice-suggestions">
							<datalist id="efc-voice-suggestions">
								<option value="af_bella"><?php esc_html_e( 'American female', 'english-finders-core' ); ?></option>
								<option value="af_nova"><?php esc_html_e( 'American female', 'english-finders-core' ); ?></option>
								<option value="am_michael"><?php esc_html_e( 'American male', 'english-finders-core' ); ?></option>
								<option value="am_onyx"><?php esc_html_e( 'American male', 'english-finders-core' ); ?></option>
								<option value="bf_emma"><?php esc_html_e( 'British female', 'english-finders-core' ); ?></option>
								<option value="bf_alice"><?php esc_html_e( 'British female', 'english-finders-core' ); ?></option>
								<option value="bm_george"><?php esc_html_e( 'British male', 'english-finders-core' ); ?></option>
								<option value="bm_daniel"><?php esc_html_e( 'British male', 'english-finders-core' ); ?></option>
							</datalist>
							<p class="description">
								<?php esc_html_e( 'Voice names are specific to the model. The suggestions above are for the default Kokoro model: those beginning "af_"/"am_" are American, "bf_"/"bm_" are British. Choosing a different model means using that model\'s own voice names.', 'english-finders-core' ); ?>
								<br>
								<?php esc_html_e( 'Changing the voice does not delete existing files. Use "Delete generated audio" below if you want everything regenerated in the new voice.', 'english-finders-core' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="efc-model"><?php esc_html_e( 'Speech model', 'english-finders-core' ); ?></label></th>
						<td>
							<input type="text" id="efc-model" name="tts_model" class="regular-text"
								value="<?php echo esc_attr( (string) ( $settings['tts_model'] ?? 'openai/gpt-4o-mini-tts' ) ); ?>">
							<p class="description">
								<?php esc_html_e( 'An OpenRouter speech model identifier. The default (Kokoro) costs roughly 40 US cents per 100,000 words and offers both American and British voices.', 'english-finders-core' ); ?>
								<br>
								<?php esc_html_e( 'A free alternative is deepgram/flux-tts:free, using a voice such as flux-brittany-en.', 'english-finders-core' ); ?>
								<br>
								<?php esc_html_e( 'Note: OpenRouter\'s documentation still shows openai/ speech models, but none are listed in their live catalogue and requests for them are rejected.', 'english-finders-core' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php $this->render_ai_fields( $settings, $has_key ); ?>

				<?php submit_button( __( 'Save settings', 'english-finders-core' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Stored audio', 'english-finders-core' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: number of files, 2: human-readable size */
					esc_html__( '%1$s generated file(s), using %2$s.', 'english-finders-core' ),
					esc_html( number_format_i18n( $file_count ) ),
					esc_html( size_format( $bytes ) ?: '0 B' )
				);
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				onsubmit="return confirm('<?php echo esc_js( __( 'Delete all generated audio files? They will be regenerated as words are next played, which will incur cost again.', 'english-finders-core' ) ); ?>');">
				<input type="hidden" name="action" value="efc_purge_audio">
				<?php wp_nonce_field( 'efc_purge_audio' ); ?>
				<?php submit_button( __( 'Delete generated audio', 'english-finders-core' ), 'delete', 'submit', false ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'CEFR levels', 'english-finders-core' ); ?></h2>
			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'Assigns a difficulty level (A1 to C2) to dictionary words. Around 7,000 levels are hand-assigned by researchers; the rest are inferred from word frequency. Words the dataset cannot place confidently are left with no level rather than being given a guessed one.', 'english-finders-core' ); ?>
			</p>

			<?php $this->render_cefr(); ?>

			<hr>

			<h2><?php esc_html_e( 'Level page preview', 'english-finders-core' ); ?></h2>
			<p class="description" style="max-width:52em">
				<?php esc_html_e( 'Counts how many words exist for each level and length combination, to decide which pages are worth generating. A page with only a handful of words ranks for nothing and weakens the section around it, so the useful figure is how many combinations clear a sensible minimum. Nothing is written and no pages are created.', 'english-finders-core' ); ?>
			</p>

			<?php $this->render_level_preview(); ?>

			<hr>

			<h2><?php esc_html_e( 'Diagnostics', 'english-finders-core' ); ?></h2>
			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'Runs one real request to the speech provider and reports exactly what came back, including the provider\'s own error message. This is the quickest way to find out why audio is not generating.', 'english-finders-core' ); ?>
			</p>

			<?php $this->render_diagnostics(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="efc_test_audio">
				<?php wp_nonce_field( 'efc_test_audio' ); ?>
				<?php submit_button( __( 'Test speech provider', 'english-finders-core' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr>
			<p class="description">
				<?php
				printf(
					/* translators: %s: version number */
					esc_html__( 'English Finders Core %s', 'english-finders-core' ),
					esc_html( Api::version() )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * AI feature settings and today's usage (1.16.0).
	 *
	 * @param array<string,mixed> $settings Stored settings.
	 * @param bool                $has_key  Whether an OpenRouter key is available.
	 */
	private function render_ai_fields( array $settings, bool $has_key ): void {
		$ai    = Api::service( 'ai' );
		$quota = $ai instanceof \EnglishFindersCore\Ai\AiService ? $ai->quota() : null;
		?>
		<h2><?php esc_html_e( 'AI features', 'english-finders-core' ); ?></h2>
		<p class="description" style="max-width:46em">
			<?php esc_html_e( 'Powers AI writing feedback in English Finders Study. Each check is one paid request to the model below, using the same OpenRouter key as speech. Signed-in learners get a small free allowance each day and Pro members a larger one; the site-wide cap stops all AI requests for the rest of the day once it is reached.', 'english-finders-core' ); ?>
		</p>

		<?php if ( ! empty( $settings['ai_enabled'] ) && ! $has_key ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'AI features are switched on, but no API key is set, so nothing will run.', 'english-finders-core' ); ?></p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'AI writing feedback', 'english-finders-core' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="ai_enabled" value="1" <?php checked( ! empty( $settings['ai_enabled'] ) ); ?>>
						<?php esc_html_e( 'Allow AI requests', 'english-finders-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Off by default. Turning this on allows the site to make paid requests to the AI provider.', 'english-finders-core' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="efc-ai-model"><?php esc_html_e( 'AI model', 'english-finders-core' ); ?></label></th>
				<td>
					<input type="text" id="efc-ai-model" name="ai_model" class="regular-text" value="<?php echo esc_attr( (string) ( $settings['ai_model'] ?? \EnglishFindersCore\Ai\OpenRouterTextClient::DEFAULT_MODEL ) ); ?>">
					<p class="description"><?php esc_html_e( 'An OpenRouter model identifier. The default, anthropic/claude-haiku-4.5, costs about half a US cent per writing check.', 'english-finders-core' ); ?></p>
				</td>
			</tr>
			<?php
			$fields = array(
				'ai_free_daily'     => array( __( 'Free checks per day', 'english-finders-core' ), \EnglishFindersCore\Ai\AiQuota::DEFAULT_FREE_DAILY, __( 'For each signed-in learner without Pro.', 'english-finders-core' ) ),
				'ai_pro_daily'      => array( __( 'Pro checks per day', 'english-finders-core' ), \EnglishFindersCore\Ai\AiQuota::DEFAULT_PRO_DAILY, __( 'For each Pro member.', 'english-finders-core' ) ),
				'ai_site_daily_cap' => array( __( 'Site-wide daily cap', 'english-finders-core' ), \EnglishFindersCore\Ai\AiQuota::DEFAULT_SITE_CAP, __( 'All AI requests on the site in one day, whoever makes them.', 'english-finders-core' ) ),
			);
			foreach ( $fields as $field => $meta ) :
				?>
				<tr>
					<th scope="row"><label for="efc-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $meta[0] ); ?></label></th>
					<td>
						<input type="number" min="0" max="10000" step="1" id="efc-<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( (string) (int) ( $settings[ $field ] ?? $meta[1] ) ); ?>" style="width:7em">
						<p class="description"><?php echo esc_html( $meta[2] ); ?></p>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php if ( null !== $quota ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: requests today, 2: daily cap */
					esc_html__( 'Today: %1$s of %2$s AI requests used.', 'english-finders-core' ),
					esc_html( number_format_i18n( $quota->site_used_today() ) ),
					esc_html( number_format_i18n( $quota->site_cap() ) )
				);
				?>
			</p>
			<?php $usage = array_slice( $ai->usage(), 0, 7, true ); ?>
			<?php if ( ! empty( $usage ) ) : ?>
				<table class="widefat striped" style="max-width:46em;margin-bottom:1em">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Day', 'english-finders-core' ); ?></th>
							<th><?php esc_html_e( 'Requests', 'english-finders-core' ); ?></th>
							<th><?php esc_html_e( 'Failed', 'english-finders-core' ); ?></th>
							<th><?php esc_html_e( 'Input tokens', 'english-finders-core' ); ?></th>
							<th><?php esc_html_e( 'Output tokens', 'english-finders-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $usage as $day => $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $day ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['requests'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['failed'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['prompt_tokens'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['completion_tokens'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'english-finders-core' ) );
		}
		check_admin_referer( 'efc_save_settings' );

		$settings = $this->settings();

		$settings['tts_enabled'] = ! empty( $_POST['tts_enabled'] );

		/*
		 * Voice names are model-specific and there is no fixed universal set —
		 * Kokoro uses af_bella, Deepgram uses flux-brittany-en, Mistral uses
		 * gb_jane_neutral, and so on. An allow-list here would reject every
		 * valid name the moment the model changed, so the value is constrained
		 * by shape instead: letters, digits, underscore, hyphen, colon and dot,
		 * which covers every published identifier while still excluding
		 * anything that could be injected into the request body.
		 */
		$voice = isset( $_POST['tts_voice'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['tts_voice'] ) ) ) : '';
		$voice = (string) preg_replace( '/[^A-Za-z0-9_\-:.]/', '', $voice );
		$settings['tts_voice'] = '' !== $voice ? $voice : 'af_bella';

		$model = isset( $_POST['tts_model'] ) ? sanitize_text_field( wp_unslash( $_POST['tts_model'] ) ) : '';
		$settings['tts_model'] = '' !== $model ? $model : 'hexgrad/kokoro-82m';

		/*
		 * A blank key field means "leave the saved key alone", not "delete it".
		 * The field is never pre-filled with the real key, so treating blank as
		 * a deletion would wipe the key every time any other setting was saved.
		 */
		if ( ! $this->key_is_constant() && isset( $_POST['openrouter_key'] ) ) {
			$key = trim( sanitize_text_field( wp_unslash( $_POST['openrouter_key'] ) ) );
			if ( '' !== $key ) {
				$settings['openrouter_key'] = $key;
			}
		}

		// 1.16.0: AI features.
		$settings['ai_enabled'] = ! empty( $_POST['ai_enabled'] );

		$ai_model = isset( $_POST['ai_model'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ai_model'] ) ) ) : '';
		$ai_model = (string) preg_replace( '/[^A-Za-z0-9_\-:.\/]/', '', $ai_model );
		$settings['ai_model'] = '' !== $ai_model ? $ai_model : \EnglishFindersCore\Ai\OpenRouterTextClient::DEFAULT_MODEL;

		foreach ( array(
			'ai_free_daily'     => \EnglishFindersCore\Ai\AiQuota::DEFAULT_FREE_DAILY,
			'ai_pro_daily'      => \EnglishFindersCore\Ai\AiQuota::DEFAULT_PRO_DAILY,
			'ai_site_daily_cap' => \EnglishFindersCore\Ai\AiQuota::DEFAULT_SITE_CAP,
		) as $field => $default ) {
			$settings[ $field ] = isset( $_POST[ $field ] ) ? min( 10000, absint( wp_unslash( $_POST[ $field ] ) ) ) : $default;
		}

		update_option( self::OPTION, $settings, false );

		wp_safe_redirect( add_query_arg( 'efc_notice', 'saved', admin_url( 'options-general.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	public function handle_purge(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'english-finders-core' ) );
		}
		check_admin_referer( 'efc_purge_audio' );

		( new AudioStore() )->purge();

		wp_safe_redirect( add_query_arg( 'efc_notice', 'purged', admin_url( 'options-general.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	/**
	 * Show environment checks and the result of the last provider test.
	 *
	 * Environment first: an unwritable uploads directory produces exactly the
	 * same visible symptom as a rejected API key, and no amount of retrying the
	 * key would reveal it.
	 */
	private function render_diagnostics(): void {
		$settings = $this->settings();
		$store    = new AudioStore();
		$key_set  = $this->key_is_constant() || '' !== trim( (string) ( $settings['openrouter_key'] ?? '' ) );

		$probe = $store->path( 'writetest' );
		$writable = false;
		if ( null !== $probe ) {
			$written  = @file_put_contents( $probe . '.tmp', 'x' ); // phpcs:ignore
			$writable = false !== $written;
			@unlink( $probe . '.tmp' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$rows = array(
			__( 'Generated audio enabled', 'english-finders-core' ) => ! empty( $settings['tts_enabled'] ),
			__( 'API key present', 'english-finders-core' )         => $key_set,
			__( 'Audio folder writable', 'english-finders-core' )   => $writable,
		);
		?>
		<table class="widefat striped" style="max-width:46em;margin-bottom:1em">
			<tbody>
			<?php foreach ( $rows as $label => $ok ) : ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td style="width:6em">
						<?php echo $ok ? '<span style="color:#008a20">&#10003; ' . esc_html__( 'Yes', 'english-finders-core' ) . '</span>' : '<span style="color:#d63638">&#10007; ' . esc_html__( 'No', 'english-finders-core' ) . '</span>'; ?>
					</td>
				</tr>
			<?php endforeach; ?>
				<tr>
					<td><?php esc_html_e( 'Audio folder', 'english-finders-core' ); ?></td>
					<td><code style="word-break:break-all"><?php echo esc_html( null === $probe ? __( 'unavailable', 'english-finders-core' ) : dirname( $probe ) ); ?></code></td>
				</tr>
			</tbody>
		</table>
		<?php

		$last = get_transient( 'efc_last_tts_test' );
		if ( ! is_array( $last ) ) {
			return;
		}
		?>
		<div class="notice <?php echo $last['ok'] ? 'notice-success' : 'notice-error'; ?> inline" style="max-width:46em;padding:.6em 1em">
			<p><strong><?php echo $last['ok'] ? esc_html__( 'Test succeeded.', 'english-finders-core' ) : esc_html__( 'Test failed.', 'english-finders-core' ); ?></strong></p>
			<p><?php echo esc_html( (string) $last['message'] ); ?></p>
			<?php if ( ! empty( $last['detail'] ) ) : ?>
				<p><strong><?php esc_html_e( 'Provider response:', 'english-finders-core' ); ?></strong></p>
				<pre style="white-space:pre-wrap;word-break:break-all;margin:0"><?php echo esc_html( (string) $last['detail'] ); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Make one real synthesis request and store the outcome for display.
	 *
	 * Deliberately writes nothing to the audio store: this is a connectivity
	 * check, and a test run should not create a cached file that then masks a
	 * later genuine failure for the same word.
	 */
	public function handle_test(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'english-finders-core' ) );
		}
		check_admin_referer( 'efc_test_audio' );

		$provider = new \EnglishFindersCore\Audio\OpenRouterTtsProvider();

		if ( ! $provider->is_configured() ) {
			set_transient(
				'efc_last_tts_test',
				array(
					'ok'      => false,
					'message' => __( 'No API key is configured, so no request was attempted.', 'english-finders-core' ),
					'detail'  => '',
				),
				HOUR_IN_SECONDS
			);
		} else {
			$settings = $this->settings();
			$result   = $provider->synthesize( 'test', (string) ( $settings['tts_voice'] ?? 'alloy' ) );

			if ( is_wp_error( $result ) ) {
				$data = $result->get_error_data();
				set_transient(
					'efc_last_tts_test',
					array(
						'ok'      => false,
						'message' => sprintf(
							/* translators: 1: error code, 2: error message */
							__( '%1$s — %2$s', 'english-finders-core' ),
							$result->get_error_code(),
							$result->get_error_message()
						),
						'detail'  => is_array( $data ) ? trim( ( isset( $data['status'] ) ? 'HTTP ' . $data['status'] . "\n" : '' ) . ( $data['detail'] ?? '' ) . ( isset( $data['model'] ) ? "\nmodel: " . $data['model'] : '' ) ) : '',
					),
					HOUR_IN_SECONDS
				);
			} else {
				set_transient(
					'efc_last_tts_test',
					array(
						'ok'      => true,
						'message' => sprintf(
							/* translators: 1: byte count, 2: mime type */
							__( 'Received %1$s bytes of %2$s. Audio generation is working.', 'english-finders-core' ),
							number_format_i18n( strlen( $result['body'] ) ),
							$result['mime']
						),
						'detail'  => '',
					),
					HOUR_IN_SECONDS
				);
			}
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/** Coverage summary plus the preview/import controls. */
	private function render_cefr(): void {
		$importer = new \EnglishFindersCore\Cefr\CefrImporter();

		if ( ! $importer->dataset_exists() ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The CEFR dataset file is missing from the plugin.', 'english-finders-core' ) . '</p></div>';
			return;
		}

		$coverage = $importer->current_coverage();
		$preview  = get_transient( 'efc_cefr_preview' );
		?>
		<table class="widefat striped" style="max-width:46em;margin-bottom:1em">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Dictionary words', 'english-finders-core' ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $coverage['dictionary_words'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Words with a level', 'english-finders-core' ); ?></td>
					<td>
						<?php echo esc_html( number_format_i18n( $coverage['with_level'] ) ); ?>
						(<?php echo esc_html( (string) $coverage['coverage_percent'] ); ?>%)
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Hand-assigned', 'english-finders-core' ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $coverage['by_source']['verified'] ) ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Estimated from frequency', 'english-finders-core' ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $coverage['by_source']['inferred'] ) ); ?></td>
				</tr>
				<?php foreach ( $coverage['by_level'] as $level => $count ) : ?>
					<?php if ( $count > 0 ) : ?>
						<tr>
							<td style="padding-left:2em"><?php echo esc_html( $level ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( is_array( $preview ) ) : ?>
			<div class="notice notice-info inline" style="max-width:46em;padding:.6em 1em">
				<p><strong><?php esc_html_e( 'Preview result — nothing was written.', 'english-finders-core' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: 1: matched count, 2: dictionary size, 3: percentage */
						esc_html__( '%1$s of your %2$s words (%3$s%%) would receive a level.', 'english-finders-core' ),
						esc_html( number_format_i18n( $preview['matched'] ) ),
						esc_html( number_format_i18n( $preview['dictionary_words'] ) ),
						esc_html( (string) $preview['coverage_percent'] )
					);
					?>
					<br>
					<?php
					printf(
						/* translators: 1: verified count, 2: inferred count */
						esc_html__( 'Hand-assigned: %1$s. Estimated: %2$s.', 'english-finders-core' ),
						esc_html( number_format_i18n( $preview['by_source']['verified'] ) ),
						esc_html( number_format_i18n( $preview['by_source']['inferred'] ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
			<input type="hidden" name="action" value="efc_cefr_import">
			<input type="hidden" name="mode" value="preview">
			<?php wp_nonce_field( 'efc_cefr_import' ); ?>
			<?php submit_button( __( 'Preview coverage', 'english-finders-core' ), 'secondary', 'submit', false ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:.5em"
			onsubmit="return confirm('<?php echo esc_js( __( 'Apply CEFR levels to the dictionary? This updates existing words but never adds new ones.', 'english-finders-core' ) ); ?>');">
			<input type="hidden" name="action" value="efc_cefr_import">
			<input type="hidden" name="mode" value="apply">
			<?php wp_nonce_field( 'efc_cefr_import' ); ?>
			<?php submit_button( __( 'Import levels', 'english-finders-core' ), 'primary', 'submit', false ); ?>
		</form>
		<?php
	}

	/** Run the importer in preview or apply mode. */
	public function handle_cefr(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'english-finders-core' ) );
		}
		check_admin_referer( 'efc_cefr_import' );

		$mode    = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'preview';
		$dry_run = 'apply' !== $mode;

		$result = ( new \EnglishFindersCore\Cefr\CefrImporter() )->run( $dry_run );

		if ( is_wp_error( $result ) ) {
			set_transient(
				'efc_cefr_preview',
				array(
					'matched'          => 0,
					'dictionary_words' => 0,
					'coverage_percent' => 0,
					'by_source'        => array( 'verified' => 0, 'inferred' => 0 ),
				),
				HOUR_IN_SECONDS
			);
		} elseif ( $dry_run ) {
			set_transient( 'efc_cefr_preview', $result, HOUR_IN_SECONDS );
		} else {
			// After a real import the live coverage table is the truth, so the
			// preview is cleared rather than left to contradict it.
			delete_transient( 'efc_cefr_preview' );
		}

		wp_safe_redirect(
			add_query_arg(
				'efc_notice',
				$dry_run ? 'previewed' : 'imported',
				admin_url( 'options-general.php?page=' . self::MENU_SLUG )
			)
		);
		exit;
	}

	/** Level/length grid and the viable-page summary. */
	private function render_level_preview(): void {
		$report = get_transient( 'efc_level_preview' );
		$threshold = is_array( $report ) ? (int) $report['threshold'] : 15;
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:1em">
			<input type="hidden" name="action" value="efc_level_preview">
			<?php wp_nonce_field( 'efc_level_preview' ); ?>
			<label>
				<?php esc_html_e( 'Minimum words per page', 'english-finders-core' ); ?>
				<input type="number" name="threshold" value="<?php echo esc_attr( (string) $threshold ); ?>" min="1" max="500" step="1" style="width:6em">
			</label>
			<?php submit_button( __( 'Preview level pages', 'english-finders-core' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php

		if ( ! is_array( $report ) ) {
			return;
		}

		if ( empty( $report['grid'] ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'No levels found. Import CEFR levels first.', 'english-finders-core' ) . '</p></div>';
			return;
		}
		?>
		<p>
			<strong>
				<?php
				printf(
					/* translators: 1: viable count, 2: total combinations, 3: threshold */
					esc_html__( '%1$s of %2$s combinations have at least %3$s words.', 'english-finders-core' ),
					esc_html( number_format_i18n( $report['viable_pages'] ) ),
					esc_html( number_format_i18n( $report['combinations_total'] ) ),
					esc_html( number_format_i18n( $report['threshold'] ) )
				);
				?>
			</strong>
			<br>
			<?php
			printf(
				/* translators: 1: thin count, 2: words covered, 3: level-only page count */
				esc_html__( '%1$s would be too thin to publish. Viable pages would cover %2$s words. Level-only pages (all lengths combined): %3$s.', 'english-finders-core' ),
				esc_html( number_format_i18n( $report['thin_pages'] ) ),
				esc_html( number_format_i18n( $report['words_on_viable'] ) ),
				esc_html( number_format_i18n( $report['level_only_pages'] ) )
			);
			?>
		</p>

		<table class="widefat striped" style="max-width:60em">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Length', 'english-finders-core' ); ?></th>
					<?php foreach ( array_keys( $report['grid'] ) as $level ) : ?>
						<th><?php echo esc_html( $level ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<?php for ( $len = 2; $len <= 15; $len++ ) : ?>
				<?php
				$row_total = 0;
				foreach ( $report['grid'] as $lengths ) {
					$row_total += (int) ( $lengths[ $len ] ?? 0 );
				}
				if ( 0 === $row_total ) {
					continue;
				}
				?>
				<tr>
					<td><strong><?php echo esc_html( (string) $len ); ?></strong></td>
					<?php foreach ( $report['grid'] as $lengths ) : ?>
						<?php
						$count  = (int) ( $lengths[ $len ] ?? 0 );
						$viable = $count >= (int) $report['threshold'];
						?>
						<td style="<?php echo $viable ? 'color:#008a20;font-weight:600' : 'color:#8c8f94'; ?>">
							<?php echo esc_html( number_format_i18n( $count ) ); ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endfor; ?>
				<tr>
					<td><strong><?php esc_html_e( 'All lengths', 'english-finders-core' ); ?></strong></td>
					<?php foreach ( $report['level_totals'] as $total ) : ?>
						<td><strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong></td>
					<?php endforeach; ?>
				</tr>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Green figures clear the threshold; grey ones do not.', 'english-finders-core' ); ?></p>
		<?php
	}

	/** Run the level page preview. */
	public function handle_level_preview(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'english-finders-core' ) );
		}
		check_admin_referer( 'efc_level_preview' );

		$threshold = isset( $_POST['threshold'] ) ? absint( wp_unslash( $_POST['threshold'] ) ) : 15;
		$threshold = max( 1, min( 500, $threshold ) );

		$report = ( new \EnglishFindersCore\Cefr\LevelPagePreview() )->report( $threshold );
		set_transient( 'efc_level_preview', $report, HOUR_IN_SECONDS );

		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::MENU_SLUG ) );
		exit;
	}
}
