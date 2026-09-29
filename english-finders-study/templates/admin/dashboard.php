<?php
/**
 * Admin dashboard.
 *
 * @var string                                  $version
 * @var array<string,\EnglishFindersStudy\Contracts\ToolInterface> $tools
 * @var list<string>                             $enabled_ids
 * @var array<string,string>                     $active_categories
 * @var int                                      $total_categories
 * @var bool                                     $core_compatible
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled_count = count( $enabled_ids );
?>
<div class="wrap efs-admin-wrap">
	<div class="efs-admin-header">
		<div>
			<h1><?php esc_html_e( 'English Finders Study', 'english-finders-study' ); ?></h1>
			<p><?php esc_html_e( 'Practice tools at a glance.', 'english-finders-study' ); ?></p>
		</div>
		<span class="efs-version"><?php echo esc_html( 'v' . $version ); ?></span>
	</div>

	<div class="efs-admin-stats">
		<div class="efs-admin-card">
			<span><?php esc_html_e( 'Tools registered', 'english-finders-study' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( count( $tools ) ) ); ?></strong>
		</div>
		<div class="efs-admin-card">
			<span><?php esc_html_e( 'Tools enabled', 'english-finders-study' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( $enabled_count ) ); ?></strong>
		</div>
		<div class="efs-admin-card">
			<span><?php esc_html_e( 'Active categories', 'english-finders-study' ); ?></span>
			<strong>
				<?php
				printf(
					/* translators: 1: active category count, 2: total category count. */
					esc_html__( '%1$d of %2$d', 'english-finders-study' ),
					count( $active_categories ),
					$total_categories
				);
				?>
			</strong>
		</div>
		<div class="efs-admin-card">
			<span><?php esc_html_e( 'Core connection', 'english-finders-study' ); ?></span>
			<strong>
				<span class="efs-status <?php echo $core_compatible ? 'is-good' : 'is-bad'; ?>">
					<?php echo $core_compatible ? esc_html__( 'Connected', 'english-finders-study' ) : esc_html__( 'Unavailable', 'english-finders-study' ); ?>
				</span>
			</strong>
		</div>
	</div>

	<div class="efs-admin-grid">
		<section class="efs-admin-panel efs-panel-wide">
			<div class="efs-panel-heading">
				<h2><?php esc_html_e( 'Tools', 'english-finders-study' ); ?></h2>
			</div>
			<div class="efs-table-wrap">
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tool', 'english-finders-study' ); ?></th>
							<th><?php esc_html_e( 'Category', 'english-finders-study' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'english-finders-study' ); ?></th>
							<th><?php esc_html_e( 'Status', 'english-finders-study' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $tools ) : ?>
							<?php foreach ( $tools as $tool ) : ?>
								<?php $is_enabled = in_array( $tool->id(), $enabled_ids, true ); ?>
								<tr>
									<td><?php echo esc_html( $tool->title() ); ?></td>
									<td><?php echo esc_html( \EnglishFindersStudy\Catalog\ToolCatalog::CATEGORIES[ $tool->category() ] ?? $tool->category() ); ?></td>
									<td><code>[<?php echo esc_html( $tool->shortcode_tag() ); ?>]</code></td>
									<td>
										<span class="efs-status <?php echo $is_enabled ? 'is-good' : 'is-bad'; ?>">
											<?php echo $is_enabled ? esc_html__( 'Enabled', 'english-finders-study' ) : esc_html__( 'Disabled', 'english-finders-study' ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No tools are registered yet.', 'english-finders-study' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section class="efs-admin-panel">
			<div class="efs-panel-heading">
				<h2><?php esc_html_e( 'Categories', 'english-finders-study' ); ?></h2>
			</div>
			<?php if ( $active_categories ) : ?>
				<ul class="efs-tag-list">
					<?php foreach ( $active_categories as $label ) : ?>
						<li><?php echo esc_html( $label ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="efs-empty"><?php esc_html_e( 'No category has an enabled tool yet.', 'english-finders-study' ); ?></p>
			<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'A category only appears in navigation once it has at least one enabled tool.', 'english-finders-study' ); ?>
			</p>
		</section>

		<section class="efs-admin-panel">
			<div class="efs-panel-heading">
				<h2><?php esc_html_e( 'Quick actions', 'english-finders-study' ); ?></h2>
			</div>
			<div class="efs-admin-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=english-finders-study-settings' ) ); ?>">
					<?php esc_html_e( 'Manage tools', 'english-finders-study' ); ?>
				</a>
			</div>
		</section>
	</div>
</div>
