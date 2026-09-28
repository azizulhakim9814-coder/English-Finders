<?php
/**
 * Plugin settings screen.
 *
 * @var array<string,\EnglishFindersStudy\Contracts\ToolInterface> $tools
 * @var list<string>                             $enabled_ids
 * @var array<string,mixed>                       $settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap efs-admin-wrap">
	<div class="efs-admin-header">
		<div>
			<h1><?php esc_html_e( 'Settings', 'english-finders-study' ); ?></h1>
			<p><?php esc_html_e( 'Choose which tools are available and how the plugin behaves on removal.', 'english-finders-study' ); ?></p>
		</div>
	</div>

	<form method="post" action="options.php" class="efs-settings-form">
		<?php settings_fields( 'efs_settings_group' ); ?>

		<div class="efs-settings-content">
			<section>
				<h2><?php esc_html_e( 'Tools', 'english-finders-study' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Each tool can be disabled independently. A disabled tool\'s shortcode stops rendering and its category disappears from navigation if nothing else in it is enabled.', 'english-finders-study' ); ?>
				</p>
				<?php if ( $tools ) : ?>
					<?php foreach ( $tools as $tool ) : ?>
						<div class="efs-setting-row">
							<div>
								<label for="efs-tool-<?php echo esc_attr( $tool->id() ); ?>"><?php echo esc_html( $tool->title() ); ?></label>
								<p><code>[<?php echo esc_html( $tool->shortcode_tag() ); ?>]</code> · <?php echo esc_html( \EnglishFindersStudy\Catalog\ToolCatalog::CATEGORIES[ $tool->category() ] ?? $tool->category() ); ?></p>
							</div>
							<label class="efs-admin-switch">
								<input
									id="efs-tool-<?php echo esc_attr( $tool->id() ); ?>"
									type="checkbox"
									name="efs_settings[enabled_tools][]"
									value="<?php echo esc_attr( $tool->id() ); ?>"
									<?php checked( in_array( $tool->id(), $enabled_ids, true ) ); ?>
								>
								<span></span>
							</label>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="efs-empty"><?php esc_html_e( 'No tools are registered yet.', 'english-finders-study' ); ?></p>
				<?php endif; ?>
			</section>

			<section>
				<h2><?php esc_html_e( 'General', 'english-finders-study' ); ?></h2>
				<div class="efs-setting-row">
					<div>
						<label for="efs-delete-on-uninstall"><?php esc_html_e( 'Delete plugin data on uninstall', 'english-finders-study' ); ?></label>
						<p><?php esc_html_e( 'When the plugin is deleted (not merely deactivated), also remove its database tables and settings. Left off, uninstalling keeps everything in place for a future reinstall.', 'english-finders-study' ); ?></p>
					</div>
					<label class="efs-admin-switch">
						<input
							id="efs-delete-on-uninstall"
							type="checkbox"
							name="efs_settings[delete_data_on_uninstall]"
							value="1"
							<?php checked( ! empty( $settings['delete_data_on_uninstall'] ) ); ?>
						>
						<span></span>
					</label>
				</div>
			</section>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
