<?php
/**
 * Core plugin bootstrap.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore;

use EnglishFindersCore\Activity\ActivityRecorder;
use EnglishFindersCore\Admin\SettingsPage;
use EnglishFindersCore\Admin\UsagePage;
use EnglishFindersCore\Assessment\LevelResultRepository;
use EnglishFindersCore\Certificates\CertificateRepository;
use EnglishFindersCore\Mistakes\MistakeRepository;
use EnglishFindersCore\Audio\AudioResolver;
use EnglishFindersCore\Audio\AudioStore;
use EnglishFindersCore\Audio\OpenRouterTtsProvider;
use EnglishFindersCore\Billing\EntitlementRepository;
use EnglishFindersCore\Billing\PaddlePortalClient;
use EnglishFindersCore\Billing\PaddleWebhookController;
use EnglishFindersCore\Billing\TransactionLog;
use EnglishFindersCore\Cefr\CefrImporter;
use EnglishFindersCore\Core\Cache;
use EnglishFindersCore\Integrations\QsmIntegration;
use EnglishFindersCore\Integrations\TutorIntegration;
use EnglishFindersCore\Models\WordRepository;
use EnglishFindersCore\Usage\UsageController;
use EnglishFindersCore\Usage\UsageRepository;
use EnglishFindersCore\Database\Installer;
use EnglishFindersCore\Database\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?self $instance = null;

	private bool $booted = false;

	/** @var array<string,callable(self):mixed> */
	private array $factories = array();

	/** @var array<string,mixed> */
	private array $resolved = array();

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

		Installer::maybe_upgrade();

		$this->register_services();

		/*
		 * Admin only. Registering the settings screen on front-end requests
		 * would add hooks to every page load for no benefit.
		 */
		if ( is_admin() ) {
			( new SettingsPage() )->register();
			( new UsagePage( $this->get( 'usage' ) ) )->register();
		}

		/*
		 * Unconditional: the footer script is printed on public pages and the
		 * REST route must exist on every request. 1.15.0.
		 */
		( new UsageController( $this->get( 'usage' ) ) )->register();

		/*
		 * Unconditional, unlike the settings screen above: Paddle posts to
		 * this route from outside WordPress entirely, so it must be
		 * registered on every request, not just admin ones.
		 */
		( new PaddleWebhookController( $this->get( 'entitlements' ) ) )->register();

		/*
		 * Unconditional, same reasoning as the webhook controller above:
		 * these listen for third-party hooks (Tutor LMS, QSM) that only
		 * fire during a real request if that plugin is active and the
		 * relevant action actually happens -- registering the listener
		 * costs nothing when the target plugin is missing. Phase A2 step 4.
		 */
		( new TutorIntegration() )->register_hooks();
		( new QsmIntegration() )->register_hooks();

		/**
		 * Fires once Core is ready and its services can be resolved.
		 *
		 * Consumer plugins should hook this rather than assuming Core is
		 * available at an arbitrary point in the load order.
		 */
		do_action( 'efc_ready', $this );
	}

	/**
	 * Register service factories.
	 *
	 * Lazy: factories are closures, so nothing is instantiated until something
	 * actually asks for it. A page that never touches the dictionary pays no
	 * cost for it being registered.
	 */
	private function register_services(): void {
		$this->singleton( 'migrator', static fn (): Migrator => new Migrator() );

		$this->singleton( 'audio_store', static fn (): AudioStore => new AudioStore() );

		$this->singleton( 'cefr', static fn (): CefrImporter => new CefrImporter() );

		/*
		 * Dictionary access. Both plugins resolve the same instance, so there is
		 * one cache layer and one query path rather than a copy per consumer.
		 */
		$this->singleton( 'cache', static fn (): Cache => new Cache() );

		$this->singleton(
			'words',
			static fn ( self $core ): WordRepository => new WordRepository( $core->get( 'cache' ) )
		);

		$this->singleton(
			'audio_provider',
			static fn (): OpenRouterTtsProvider => new OpenRouterTtsProvider()
		);

		/*
		 * The resolver is the only audio service consumers should need. It owns
		 * the fallback chain, so a consumer never has to know whether a given
		 * word was recorded, generated, or unavailable.
		 */
		$this->singleton(
			'audio',
			static fn ( self $core ): AudioResolver => new AudioResolver(
				$core->get( 'audio_store' ),
				$core->get( 'audio_provider' )
			)
		);

		/*
		 * The one thing any plugin should ask "what is this user entitled to".
		 * Word Games Pro and English Finders Study resolve this same instance
		 * rather than querying the entitlements table themselves, for the same
		 * reason `words` above is shared rather than duplicated per consumer.
		 */
		$this->singleton( 'entitlements', static fn (): EntitlementRepository => new EntitlementRepository() );

		/*
		 * Phase A5. Both read-only/side-effect-light: the portal client only
		 * requests a session URL (nothing is charged or changed by asking
		 * for one), and the transaction log only reads billing_events.
		 */
		$this->singleton( 'paddle_portal', static fn (): PaddlePortalClient => new PaddlePortalClient() );
		$this->singleton( 'transactions', static fn (): TransactionLog => new TransactionLog() );

		/*
		 * Phase A2. The one entry point WGP/EFS/EFA and future Tutor/QSM
		 * integrations should call to record activity -- see
		 * ActivityRecorder's own docblock. No emitters are wired to it yet
		 * (that is step 2+ of A2's build sequence); this ships dormant.
		 */
		// 1.13.0: the recorder asks the entitlements service whether a user has Pro (monthly streak-freeze top-up).
		$this->singleton(
			'activity',
			fn (): ActivityRecorder => new ActivityRecorder(
				null,
				null,
				fn ( int $user_id ): bool => $this->get( 'entitlements' )->for_user( $user_id )->unlocks_pro()
			)
		);

		/*
		 * Phase A4. English Level Test results -- English Finders Study
		 * writes (it runs the test), English Finders Account reads (My
		 * Level). See LevelResultRepository's own docblock.
		 */
		$this->singleton( 'level_results', static fn (): LevelResultRepository => new LevelResultRepository() );

		/*
		 * Mistake notebook (1.9.0). English Finders Study records misses and
		 * later correct answers; English Finders Account reads the notebook.
		 * See MistakeRepository's own docblock.
		 */
		$this->singleton( 'mistakes', static fn (): MistakeRepository => new MistakeRepository() );

		/*
		 * Course-completion certificates (1.12.0). Issued by TutorIntegration
		 * on course completion; English Finders Account reads them.
		 */
		$this->singleton( 'certificates', static fn (): CertificateRepository => new CertificateRepository() );

		/*
		 * Per-tool usage counts (1.15.0), fed by UsageController from the
		 * browser's ef:progress events. See UsageRepository's own docblock.
		 */
		$this->singleton( 'usage', static fn (): UsageRepository => new UsageRepository() );
	}

	/**
	 * @param string                $id      Service identifier.
	 * @param callable(self):mixed  $factory Factory closure.
	 */
	public function singleton( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || array_key_exists( $id, $this->resolved );
	}

	/**
	 * Resolve a service.
	 *
	 * @param string $id Service identifier.
	 * @return mixed
	 */
	public function get( string $id ): mixed {
		if ( array_key_exists( $id, $this->resolved ) ) {
			return $this->resolved[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			return null;
		}

		$this->resolved[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->resolved[ $id ];
	}

	public function version(): string {
		return EFC_VERSION;
	}

	public function db_version(): string {
		return EFC_DB_VERSION;
	}
}
