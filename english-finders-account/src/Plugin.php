<?php
/**
 * Plugin bootstrap.
 *
 * @package EnglishFindersAccount
 */

declare(strict_types=1);

namespace EnglishFindersAccount;

use EnglishFindersAccount\Auth\GoogleLogin;
use EnglishFindersAccount\Auth\LoginHandler;
use EnglishFindersAccount\Auth\PasswordChangeHandler;
use EnglishFindersAccount\Profile\Avatar;
use EnglishFindersAccount\Auth\RegistrationHandler;
use EnglishFindersAccount\Certificates\CertificatePage;
use EnglishFindersAccount\Leaderboard\LeaderboardOptinHandler;
use EnglishFindersAccount\Leaderboard\PublicBoardFile;
use EnglishFindersAccount\Leaderboard\WeeklyTopWidget;
use EnglishFindersAccount\Membership\AdFree;
use EnglishFindersAccount\Membership\BillingSettingsPage;
use EnglishFindersAccount\Membership\PricingPage;
use EnglishFindersAccount\Membership\ProPromo;
use EnglishFindersAccount\Security\PublicProfileGuard;
use EnglishFindersAccount\Security\RegistrationGate;
use EnglishFindersAccount\Security\SuspiciousSignups;
use EnglishFindersAccount\Learning\TutorLoginBridge;
use EnglishFindersAccount\Learning\TutorProgressBridge;
use EnglishFindersAccount\Membership\MembershipController;
use EnglishFindersAccount\Mistakes\ResolveMistakeHandler;
use EnglishFindersAccount\Pages\AuthPages;
use EnglishFindersAccount\Pages\GuestNudge;
use EnglishFindersAccount\Pages\MyAccountController;
use EnglishFindersAccount\Privacy\PrivacyIntegration;
use EnglishFindersAccount\Profile\ProfileRepository;
use EnglishFindersAccount\Profile\ProfileSaveHandler;
use EnglishFindersAccount\Support\CachePolicy;
use EnglishFindersAccount\Support\Toolbar;

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

		$this->register_services();

		( new RegistrationHandler() )->register_hooks();
		( new LoginHandler() )->register_hooks();
		( new GoogleLogin() )->register_hooks();
		( new PasswordChangeHandler() )->register_hooks();
		( new Avatar() )->register_hooks();
		( new ProfileSaveHandler() )->register_hooks();
		( new PrivacyIntegration() )->register_hooks();
		( new MembershipController() )->register_hooks();
		( new ResolveMistakeHandler() )->register_hooks();
		( new LeaderboardOptinHandler() )->register_hooks();
		( new PublicBoardFile() )->register_hooks();
		( new WeeklyTopWidget() )->register_hooks();
		( new AdFree() )->register_hooks();
		( new PricingPage() )->register_hooks();
		( new ProPromo() )->register_hooks();
		( new BillingSettingsPage() )->register_hooks();
		( new RegistrationGate() )->register_hooks();
		( new PublicProfileGuard() )->register_hooks();
		( new SuspiciousSignups() )->register_hooks();
		( new TutorProgressBridge() )->register_hooks();
		( new TutorLoginBridge() )->register_hooks();
		( new CachePolicy() )->register_hooks();
		( new Toolbar() )->register_hooks();
		( new AuthPages() )->register_hooks();
		( new GuestNudge() )->register_hooks();
		( new CertificatePage() )->register_hooks();
		( new MyAccountController() )->register_hooks();

		/**
		 * Fires once the account plugin is ready. Mirrors Core's efc_ready
		 * hook -- a later phase (A5) that needs to react to an account
		 * existing should hook this rather than assume load order.
		 */
		do_action( 'efa_ready', $this );
	}

	private function register_services(): void {
		$this->singleton( 'profiles', static fn (): ProfileRepository => new ProfileRepository() );
	}

	/**
	 * @param string               $id      Service identifier.
	 * @param callable(self):mixed $factory Factory closure.
	 */
	public function singleton( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || array_key_exists( $id, $this->resolved );
	}

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
		return EFA_VERSION;
	}
}
