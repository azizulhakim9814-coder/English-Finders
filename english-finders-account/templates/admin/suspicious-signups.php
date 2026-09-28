<?php
/**
 * Users -> Suspicious sign-ups (0.16.0) -- see Security\SuspiciousSignups.
 *
 * The form submits to WordPress's own users.php?action=delete, which shows
 * its normal confirmation screen; nothing is deleted from here.
 *
 * @package EnglishFindersAccount
 *
 * @var list<array{id:int,login:string,registered:string,domain:string,reasons:list<string>}> $rows
 * @var int $total
 * @var int $paged
 * @var int $pages
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Suspicious sign-ups', 'english-finders-account' ); ?></h1>
	<p style="max-width:760px">
		<?php esc_html_e( 'Accounts with hard evidence of spam: links in their profile (Tutor bio, social fields, biography or website) or a pending instructor application. Anyone who shows real use is never listed: staff, approved instructors, learners with any XP, a course enrolment or an approved comment, and accounts made through the sign-up page or Google.', 'english-finders-account' ); ?>
	</p>
	<p style="max-width:760px">
		<?php esc_html_e( 'Nothing is deleted here. Tick the accounts to remove and press the button: WordPress then shows its own Delete Users confirmation, where you decide.', 'english-finders-account' ); ?>
	</p>

	<?php if ( 0 === $total ) : ?>
		<div class="notice notice-success inline"><p><?php esc_html_e( 'No suspicious accounts found.', 'english-finders-account' ); ?></p></div>
	<?php else : ?>
		<form method="get" action="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
			<input type="hidden" name="action" value="delete">
			<?php wp_nonce_field( 'bulk-users', '_wpnonce', false ); ?>

			<div class="tablenav top">
				<div class="alignleft actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Review selected on the Delete Users screen', 'english-finders-account' ); ?></button>
				</div>
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						/* translators: %s: number of accounts */
						echo esc_html( sprintf( _n( '%s account', '%s accounts', $total, 'english-finders-account' ), number_format_i18n( $total ) ) );
						?>
					</span>
					<?php if ( $pages > 1 ) : ?>
						<span class="pagination-links">
							<?php
							echo wp_kses_post(
								(string) paginate_links(
									array(
										'base'    => add_query_arg( 'paged', '%#%' ),
										'format'  => '',
										'current' => $paged,
										'total'   => $pages,
									)
								)
							);
							?>
						</span>
					<?php endif; ?>
				</div>
				<br class="clear">
			</div>

			<table class="wp-list-table widefat fixed striped users">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"><label for="cb-select-all-1" class="screen-reader-text"><?php esc_html_e( 'Select all', 'english-finders-account' ); ?></label></td>
						<th scope="col" class="manage-column column-username"><?php esc_html_e( 'Username', 'english-finders-account' ); ?></th>
						<th scope="col" class="manage-column"><?php esc_html_e( 'Registered', 'english-finders-account' ); ?></th>
						<th scope="col" class="manage-column"><?php esc_html_e( 'Email domain', 'english-finders-account' ); ?></th>
						<th scope="col" class="manage-column" style="width:40%"><?php esc_html_e( 'Why it is listed', 'english-finders-account' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $efa_row ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="users[]" id="efa-user-<?php echo esc_attr( (string) $efa_row['id'] ); ?>" value="<?php echo esc_attr( (string) $efa_row['id'] ); ?>">
								<label for="efa-user-<?php echo esc_attr( (string) $efa_row['id'] ); ?>" class="screen-reader-text"><?php echo esc_html( $efa_row['login'] ); ?></label>
							</th>
							<td class="column-username"><a href="<?php echo esc_url( get_edit_user_link( $efa_row['id'] ) ); ?>"><strong><?php echo esc_html( $efa_row['login'] ); ?></strong></a></td>
							<td><?php echo esc_html( mysql2date( 'j M Y', $efa_row['registered'] ) ); ?></td>
							<td><?php echo esc_html( $efa_row['domain'] ); ?></td>
							<td><?php echo esc_html( implode( ' · ', $efa_row['reasons'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</form>
	<?php endif; ?>
</div>
