<?php
/**
 * Admin dashboard and settings.
 *
 * `efs_settings` has carried `disabled_tools` (originally `enabled_tools`)
 * and `delete_data_on_uninstall` since this plugin's first release —
 * `uninstall.php` already reads the latter — but nothing has ever let an
 * admin actually set either. This is that missing surface, scoped to what
 * this plugin actually has: which tools exist and whether each is on, plus
 * the one uninstall-behaviour toggle.
 * Word Games Pro's admin covers dictionary import, cache and analytics
 * because it has a dictionary, a cache and an analytics table; this plugin
 * has none of those, so neither does this screen.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Admin;

use EnglishFindersStudy\Catalog\ToolCatalog;
use EnglishFindersStudy\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminController {
	private const CAPABILITY = 'manage_options';
	private const MENU_SLUG  = 'english-finders-study';

	public function __construct( private readonly ToolCatalog $tools ) {}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function menu(): void {
		add_menu_page(
			__( 'English Finders Study', 'english-finders-study' ),
			__( 'Study', 'english-finders-study' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'dashboard_page' ),
			'dashicons-welcome-learn-more',
			59
		);
		add_submenu_page( self::MENU_SLUG, __( 'Dashboard', 'english-finders-study' ), __( 'Dashboard', 'english-finders-study' ), self::CAPABILITY, self::MENU_SLUG, array( $this, 'dashboard_page' ) );
		add_submenu_page( self::MENU_SLUG, __( 'Settings', 'english-finders-study' ), __( 'Settings', 'english-finders-study' ), self::CAPABILITY, self::MENU_SLUG . '-settings', array( $this, 'settings_page' ) );
	}

	/**
	 * Load the admin stylesheet only on this plugin's own screens.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style( 'efs-admin', EFS_URL . 'assets/css/admin.css', array(), EFS_VERSION );
	}

	public function register_settings(): void {
		register_setting(
			'efs_settings_group',
			'efs_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * @param mixed $input Raw form submission.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( mixed $input ): array {
		$input = is_array( $input ) ? $input : array();

		/*
		 * The form posts which tools are *checked* (enabled) — the natural
		 * shape for a settings screen, matching every other toggle here. What
		 * gets stored is the complement: `disabled_tools`, the ids that were
		 * left unchecked. Computing the complement here, against the full
		 * known id set, is what makes "uncheck every tool" store correctly as
		 * "everything disabled" rather than colliding with the separate,
		 * unrelated meaning `ToolCatalog::is_enabled()` gives to an empty
		 * list. See that method for why the storage shape is inverted from
		 * the form's.
		 */
		$known_ids   = array_keys( $this->tools->all_registered() );
		$checked_ids = array_map( 'sanitize_key', (array) ( $input['enabled_tools'] ?? array() ) );
		$disabled    = array_values( array_diff( $known_ids, $checked_ids ) );

		return array(
			'disabled_tools'           => $disabled,
			'delete_data_on_uninstall' => empty( $input['delete_data_on_uninstall'] ) ? 0 : 1,
		);
	}

	public function dashboard_page(): void {
		$this->guard();

		$registered = $this->tools->all_registered();
		$enabled    = $this->tools->all();

		$this->render(
			'dashboard',
			array(
				'version'           => EFS_VERSION,
				'tools'             => $registered,
				'enabled_ids'       => array_keys( $enabled ),
				'active_categories' => $this->tools->active_categories(),
				'total_categories'  => count( ToolCatalog::CATEGORIES ),
				'core_compatible'   => Plugin::instance()->core_is_compatible(),
			)
		);
	}

	public function settings_page(): void {
		$this->guard();

		$settings = get_option( 'efs_settings', array() );

		$this->render(
			'settings',
			array(
				'tools'       => $this->tools->all_registered(),
				'enabled_ids' => array_values(
					array_map(
						static fn ( $tool ): string => $tool->id(),
						$this->tools->all()
					)
				),
				'settings'    => is_array( $settings ) ? $settings : array(),
			)
		);
	}

	/**
	 * Load an admin template with the given data in scope.
	 *
	 * No general-purpose Template class exists in this plugin — every tool
	 * renders its own markup inline via output buffering instead. Admin pages
	 * are different: they echo straight to the response rather than returning
	 * a string to compose into a shortcode, so a plain `require` is enough and
	 * a reusable renderer would be machinery this plugin has no second use
	 * for yet.
	 *
	 * @param array<string,mixed> $data Variables to expose to the template.
	 */
	private function render( string $template, array $data = array() ): void {
		/*
		 * Named `__template`/`__data` rather than `$template`/`$data` so
		 * neither collides with a data key of the same name and gets silently
		 * dropped by EXTR_SKIP.
		 */
		$__template = $template;
		$__data     = $data;
		unset( $template, $data );

		extract( $__data, EXTR_SKIP );
		require EFS_PATH . 'templates/admin/' . $__template . '.php';
	}

	private function guard(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'english-finders-study' ) );
		}
	}
}
