<?php
/**
 * Settings -> English Finders Usage (1.15.0).
 *
 * Read-only report of the counts UsageRepository keeps: which practice
 * tools and games are actually used, by visitors and members alike.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Admin;

use EnglishFindersCore\Usage\UsageRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsagePage {
	private const MENU_SLUG  = 'efc-usage';
	private const CAPABILITY = 'manage_options';

	/** Offered report windows, in days. */
	private const RANGES = array( 7, 30, 90 );

	public function __construct( private readonly UsageRepository $usage ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu(): void {
		add_options_page(
			__( 'English Finders Usage', 'english-finders-core' ),
			__( 'English Finders Usage', 'english-finders-core' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/** "efs-grammar-quiz" -> "Grammar Quiz", "wgp-daily-unscramble" -> "Daily Unscramble". */
	private static function label( string $source ): string {
		return ucwords( str_replace( '-', ' ', (string) preg_replace( '/^(efs|wgp)-/', '', $source ) ) );
	}

	private static function plugin_name( string $source ): string {
		return str_starts_with( $source, 'efs-' ) ? __( 'Practice tool', 'english-finders-core' ) : __( 'Game', 'english-finders-core' );
	}

	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'english-finders-core' ) );
		}

		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only report filter.
		$days = in_array( $days, self::RANGES, true ) ? $days : 30;

		$totals = $this->usage->totals( $days );
		$daily  = $this->usage->daily_visits( min( $days, 30 ) );
		$max    = max( 1, ...array_values( $daily ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'English Finders Usage', 'english-finders-core' ); ?></h1>
			<p class="description" style="max-width:52em">
				<?php esc_html_e( 'How often each practice tool and game is used, by everyone including visitors who are not signed in. A visit is a page view in which the tool registered at least one correct answer, solved puzzle or finished round. Only daily totals are stored: no names, IP addresses or cookies. Counting started when English Finders Core 1.15.0 was installed.', 'english-finders-core' ); ?>
			</p>

			<ul class="subsubsub">
				<?php foreach ( self::RANGES as $i => $range ) : ?>
					<?php
					$range_url = add_query_arg(
						array(
							'page' => self::MENU_SLUG,
							'days' => $range,
						),
						admin_url( 'options-general.php' )
					);
					?>
					<li>
						<a href="<?php echo esc_url( $range_url ); ?>"<?php echo $range === $days ? ' class="current" aria-current="page"' : ''; ?>>
							<?php
							/* translators: %d: number of days */
							echo esc_html( sprintf( __( 'Last %d days', 'english-finders-core' ), $range ) );
							?>
						</a><?php echo $i < count( self::RANGES ) - 1 ? ' |' : ''; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<br class="clear">

			<h2><?php esc_html_e( 'By tool', 'english-finders-core' ); ?></h2>
			<?php if ( array() === $totals ) : ?>
				<p><?php esc_html_e( 'Nothing recorded in this period yet.', 'english-finders-core' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:60em">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Tool', 'english-finders-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'english-finders-core' ); ?></th>
							<th scope="col" class="num"><?php esc_html_e( 'Visits', 'english-finders-core' ); ?></th>
							<th scope="col" class="num"><?php esc_html_e( 'Correct answers', 'english-finders-core' ); ?></th>
							<th scope="col" class="num"><?php esc_html_e( 'Puzzles solved', 'english-finders-core' ); ?></th>
							<th scope="col" class="num"><?php esc_html_e( 'Rounds finished', 'english-finders-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $totals as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( self::label( $row['source'] ) ); ?></strong> <code><?php echo esc_html( $row['source'] ); ?></code></td>
								<td><?php echo esc_html( self::plugin_name( $row['source'] ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format_i18n( $row['visit'] ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format_i18n( $row['correct'] ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format_i18n( $row['solved'] ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format_i18n( $row['finished'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Visits per day', 'english-finders-core' ); ?></h2>
			<table class="widefat striped" style="max-width:40em">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Day', 'english-finders-core' ); ?></th>
						<th scope="col" class="num"><?php esc_html_e( 'Visits', 'english-finders-core' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Bar', 'english-finders-core' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_reverse( $daily, true ) as $day => $visits ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ), ( new \DateTimeImmutable( $day, wp_timezone() ) )->getTimestamp() ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $visits ) ); ?></td>
							<td style="width:50%"><span aria-hidden="true" style="display:block;height:10px;border-radius:2px;background:#2271b1;width:<?php echo esc_attr( (string) round( 100 * $visits / $max ) ); ?>%"></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
