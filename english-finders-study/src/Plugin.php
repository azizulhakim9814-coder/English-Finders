<?php
/**
 * Plugin bootstrap.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy;

use EnglishFindersStudy\Admin\AdminController;
use EnglishFindersStudy\Catalog\ToolCatalog;
use EnglishFindersStudy\Database\Installer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?self $instance = null;

	private bool $booted = false;

	private ?ToolCatalog $tools = null;

	private function __construct() {}

	public static function instance(): self {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		/*
		 * Core version guard.
		 *
		 * The `Requires Plugins` header enforces that Core is active, but
		 * WordPress does not check versions — so an older Core would satisfy
		 * the header and then fail at the first call to a service it does not
		 * have. Failing here with an admin notice is far easier to diagnose
		 * than a fatal deep inside a tool.
		 */
		if ( ! $this->core_is_compatible() ) {
			add_action( 'admin_notices', array( $this, 'render_core_notice' ) );
			return;
		}

		Installer::maybe_upgrade();

		$this->tools = new ToolCatalog();

		/**
		 * Register practice tools.
		 *
		 * The extension point: a tool registers itself here rather than being
		 * hardcoded into this class.
		 *
		 * @param ToolCatalog $tools Catalog instance.
		 */
		/*
		 * Built-in tools register first, so a filter can inspect or replace
		 * them rather than only appending.
		 */
		$this->tools->register( new \EnglishFindersStudy\Tools\VocabularyQuiz() );
		$this->tools->register( new \EnglishFindersStudy\Tools\DefinitionMatch() );
		$this->tools->register( new \EnglishFindersStudy\Tools\GrammarQuiz() );
		$this->tools->register( new \EnglishFindersStudy\Tools\SpellingQuiz() );
		$this->tools->register( new \EnglishFindersStudy\Tools\ReadingQuiz() );
		$this->tools->register( new \EnglishFindersStudy\Tools\SentenceBuilder() );
		$this->tools->register( new \EnglishFindersStudy\Tools\ErrorCorrection() );
		$this->tools->register( new \EnglishFindersStudy\Tools\PronunciationPractice() );
		$this->tools->register( new \EnglishFindersStudy\Tools\WritingFeedback() );

		do_action( 'efs_register_tools', $this->tools );

		( new AdminController( $this->tools ) )->register();

		$this->tools->register_all();

		/*
		 * Phase A4. Registered outside the tool catalog on purpose -- it
		 * spans three skill categories, and the catalog allows exactly one.
		 * See LevelTest's own docblock.
		 */
		( new \EnglishFindersStudy\Assessment\LevelTest() )->register();

		// 1.14.0: "Practice what you just read" box at the end of blog posts.
		( new \EnglishFindersStudy\Content\PracticeBox() )->register();

		/**
		 * Fires once the plugin is ready and its tools are registered.
		 *
		 * @param self $plugin Plugin instance.
		 */
		do_action( 'efs_ready', $this );
	}

	/** Whether Core is present and new enough. */
	public function core_is_compatible(): bool {
		return class_exists( '\\EnglishFindersCore\\Support\\Api' )
			&& \EnglishFindersCore\Support\Api::is_at_least( EFS_REQUIRES_CORE );
	}

	public function render_core_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: required version number */
					__( 'English Finders Study needs English Finders Core %s or later. Its tools are inactive until Core is updated.', 'english-finders-study' ),
					EFS_REQUIRES_CORE
				)
			)
		);
	}

	public function tools(): ?ToolCatalog {
		return $this->tools;
	}

	/**
	 * Resolve a service from Core.
	 *
	 * Everything shared — the dictionary, CEFR levels, AI and speech providers,
	 * entitlements — is reached through here rather than reimplemented. Returns
	 * null when Core is unavailable, so callers degrade instead of fataling.
	 *
	 * @param string $id Core service identifier.
	 * @return mixed
	 */
	public function core( string $id ): mixed {
		if ( ! $this->core_is_compatible() ) {
			return null;
		}

		return \EnglishFindersCore\Support\Api::service( $id );
	}

	public function version(): string {
		return EFS_VERSION;
	}
}
