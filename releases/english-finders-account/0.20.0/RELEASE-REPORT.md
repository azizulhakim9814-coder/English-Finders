# English Finders Account 0.20.0: release report

**Released:** 2026-09-28 · **Previous:** 0.19.1 · **Schema:** no change · **Deployed to:** englishfinders.com

Part of Recommendation 2 (first-visit path: sign-up → Level Test → course). The site-level change that goes with it, a top-level "Level Test" item in the primary menu, is recorded at the end of this report.

## What changed

- **First steps after sign-up** (`Pages\WelcomeNote::first_url()`), for both the sign-up form and new Google sign-in accounts:
  - A **return address** (the page the learner signed up from) always wins. A learner with no level result gets `?efa_welcome=1` added to it.
  - With no return address, a **learner with no level result** goes to `/english-level-test/?efa_welcome=1`.
  - **Teachers**, and learners whose anonymous test result was just claimed on `user_register`, go to **My Account**, whose Home card already names the course to start.
- **Welcome banner** at `wp_body_open`: shown only when `efa_welcome` is present, the visitor is signed in, and they are a learner with no level result. On the test page it says to start below; on other pages it links to the test. That response is sent with `nocache_headers()` and LiteSpeed no-cache.
- `LevelController::test_page_url()` is now public and static.
- **Filters:** `efa_after_signup_url`, `efa_welcome_note_enabled`.

## Verification

| Check | Result |
|---|---|
| `php -l` on every changed file | pass |
| WPCS on `WelcomeNote.php` | clean apart from the project-wide conventions (PSR-4 file names, `$return` parameter name, as elsewhere in this plugin) |
| Local dry run of the deploy script against the 0.19.1 baseline | output identical to repo |
| Live: files hash-checked after the swap | 7/7 match |
| Live: `/`, `/sign-up/`, `/login/`, `/my-account/`, `/english-level-test/`, `/grammar-quiz/` | 200, no fatal errors, no banner for guests |
| Live `first_url()`: learner, no return | `/english-level-test/?efa_welcome=1` |
| Live `first_url()`: learner, return `/grammar-quiz/` | `/grammar-quiz/?efa_welcome=1` |
| Live `first_url()`: learner, foreign return URL | rejected → `/english-level-test/?efa_welcome=1` |
| Live `first_url()`: learner with a result (with/without return) | `/my-account/` · `/word-ladder/` (no banner) |
| Live `first_url()`: teacher, no return | `/my-account/` |
| Live, signed in as a temporary test learner (since deleted): test page with `?efa_welcome=1` | banner "start below"; `no-store, private`; LiteSpeed `no-cache` |
| Live, same learner: `/grammar-quiz/?efa_welcome=1` | banner with the test link; not cached |
| Live, same learner: `/grammar-quiz/` without the parameter | no banner |

Not tested end to end: a real form submission, which needs Cloudflare Turnstile, and a real Google sign-in. Both call the same `first_url()` verified above.

## Deploy notes

- The live 0.19.1 folder was backed up first to `~/domains/englishfinders.com/ef-backups/english-finders-account-0.19.1-20260928-082403/`.
- Deployed as exact-match edits plus one new file, hash-checked before anything was written (the same method as Core 1.15.0).

## Site change in the same step: primary menu

- Added top-level **"Level Test"** (`/english-level-test/`, menu item 35880, CSS class `ef-nav-level-test`) between Learn and Practice, in both the desktop and mobile menus.
- The previous item order is saved in the option `ef_nav_before_level_test_20260928` for rollback.
- LiteSpeed was purged **by post ID** for all 84 published pages and courses (never purge-all: that cold-starts ~64k dictionary pages). Other cached pages, such as posts and dictionary pages, pick up the new menu as their 7-day cache expires.

## Rollback

- **Plugin:** copy the backup folder over `wp-content/plugins/english-finders-account/`.
- **Menu:** delete menu item 35880; the other items' positions are cosmetic.

## Artifacts

| File | SHA-256 |
|---|---|
| `english-finders-account-0.20.0.zip` | `2a4f704a30bafe0bd745381665777b2465cc76b68d0430d95473bb4865c0fdab` |
| `english-finders-account-0.20.0-source.zip` | `2fb65838a11c114e741183f92eb0ac6ac1e41a46f17fcfa88a800adde60ce35d` |
| `english-finders-account-0.19.1-to-0.20.0.patch` | `429ca25f203d695f27603000183379858de3fbcdaac1f4f797b347b639a3a7c3` |

## Production file manifest (SHA-256)

```
8a6ae8d43c355ebf33e66a1eb2b2ffa2876e4ba9a21a08dcaf5518a87e3118cd  english-finders-account/assets/css/account.css
5973afffb60ba3e4db777f7be0def6f2129cef7c0a692d8e0e1b918768858b59  english-finders-account/assets/js/auth-forms.js
ae33fef8c056d6d63dc41d959d669acb1a12261339118e071f9dfeec74524a3b  english-finders-account/assets/js/avatar-picker.js
395f2175cfce3ee386f104d91f713b85f49d370208daad2c029845a7c85c05f6  english-finders-account/assets/js/checkout.js
6c809d9824827098df12bd3cebf366aae2f1b813322fb7a102dd1da77984fd2a  english-finders-account/assets/js/guest-nudge.js
d590e56e045fd17c73f68c8bd7cb505953840cc3ecc5a82f2e01c19fc6cfe220  english-finders-account/english-finders-account.php
81a8505e9b600d0ab138a5d6e9878f80c8da90cd7d49ac5ae0effbacf5bdb386  english-finders-account/readme.txt
abf73ca6d6136fa06c1d326e739e4ab38741a17189845f4455cf16256b76d4ad  english-finders-account/src/Auth/GoogleLogin.php
072bed8919872a90a30a2903388b1beebc260014914738398f07fe35ba6301b5  english-finders-account/src/Auth/LoginHandler.php
850520bcb81cabe22c519daff02945250daf46c098f1f5cd9cdc70c915a29928  english-finders-account/src/Auth/PasswordChangeHandler.php
dee4115bdf418e6096a7d7d8b27bca2064610fd97fca4102190f8cfd615515a9  english-finders-account/src/Auth/RegistrationHandler.php
18bd7f930d4da26572c4cef8326a9e524d9a6eee8ff2697ca8118ac77eae577e  english-finders-account/src/Auth/Usernames.php
4fec27fbc3eca5b38efb2c01d27ce3909eb78688db9caf29d80b0a02aa766218  english-finders-account/src/Certificates/CertificatePage.php
7e65fab99ab462db358cd53b0dd7ca42d5d38923a1507b05b77b4f96259d105d  english-finders-account/src/Certificates/CertificatesController.php
a001a0ca2c932e6d529b2218193fe3d58364a6f75c607594e9d6210dea578c00  english-finders-account/src/Core/Activator.php
85947f8152e1e633f50654cea4b7b0ba6648ca8b2bab8c7209dea685f8b159ee  english-finders-account/src/Core/Autoloader.php
7b0ffc5ceb7a6b3ea5586f21d3163a6e7cee604fb844c9e035fbace90d01a4c7  english-finders-account/src/Core/Deactivator.php
2b5cb119e5a948b07dab6a48d342ae66876fc28eeebbabcd9588ca8e4b718f8e  english-finders-account/src/Leaderboard/LeaderboardController.php
b049b9645628c230e6647bf965227f40aae9d3fb17cc2d3a1c547a27ddb0d6e1  english-finders-account/src/Leaderboard/LeaderboardOptinHandler.php
c651a169e50afdbf40f7c3e809af37b3efd716d114ac0072ccac1819fc662087  english-finders-account/src/Leaderboard/PublicBoardFile.php
d15a73481fc5837b2dd08137b88fec12c882be9ef79438e062217b4e52e0a995  english-finders-account/src/Leaderboard/WeeklyTopWidget.php
b3f415fb0a2b04736e71309e0a156c500e7b1b29f574925a2ae5aa2835ae7d4a  english-finders-account/src/Learning/CourseProgress.php
2ad7a9198756d85a8b617937cb35398e5eafab1977a83e8a24548c8d61263739  english-finders-account/src/Learning/TutorLoginBridge.php
224624462b6bb69c7fecf367450b1afad598fbda1db77b19c0eb1f7e2822afba  english-finders-account/src/Learning/TutorProgressBridge.php
0010560f4c4f2210b81015f0320c2be688e5128e5b1fd4087ff62926caa838ec  english-finders-account/src/Level/LevelController.php
35f4e98b6ff5b3eec70224dd716274effe755e4c4c3a862c056a036e8f11600a  english-finders-account/src/Library/LibraryController.php
8ff22a94a3a5427907d6ea39cd68ea8ca0a083078c50418611bdb68e4c7bab2a  english-finders-account/src/Membership/AdFree.php
c78b03f583efcff112788e334598bf3a9d800884973fb2ed4ba9ce02cdad08b1  english-finders-account/src/Membership/BillingSettingsPage.php
90438e83ca1545f07831fffa9c39d76402cd490a6aa70b08235faa6f56b5ba69  english-finders-account/src/Membership/MembershipController.php
862c78919596b55eb8ca2032349dd36cb9b78dbe6c4f41f62cc946bccfb433f0  english-finders-account/src/Membership/PricingPage.php
87884c094eba14758bc68c911f30703f93d0b7004be25717b87408bdf5b9219d  english-finders-account/src/Membership/ProOffer.php
71391e802132c9a2e495a2484d1e504d5942e1143cdc0504133998d6ed65f823  english-finders-account/src/Membership/ProPromo.php
c10df8ca23f2ddb6eefb05d198616539dff750fc0fbf8969ee4aea89270618ab  english-finders-account/src/Mistakes/MistakesController.php
1bda733da4eafa8b7cbe3f7956e925d93e1a364db43af59a9b0c512f919c7d04  english-finders-account/src/Mistakes/ResolveMistakeHandler.php
b9360716335166fe28c75019b2ea0131df19f72beb6351256e4ce6aaad86c927  english-finders-account/src/Pages/AuthPages.php
ef2a0786c49ea0198f27750afeaff5a558aa7c339ce4281237e7093cb6bd8cf8  english-finders-account/src/Pages/AuthView.php
ffc31499ab89615affa0576fc12b7de1a78130299d02ed6c73ef627787a4346a  english-finders-account/src/Pages/GuestNudge.php
471532a24bda2ac01037357c537ca7fb77c02b88bcdd86028acd859ea8fe399f  english-finders-account/src/Pages/HomeController.php
ce72b394c14dc1b652db325fd71aa59e9f2395d85dd3539f35e94eb8f07985be  english-finders-account/src/Pages/MyAccountController.php
a8b33851f03c66b36b37491fdf8777e2750230e3820a2efafdf3842b8dc7433d  english-finders-account/src/Pages/WelcomeNote.php
ea587248d05ffa1aa88799478b4e7049cff752095c0b2070d5db476c9ef0a21e  english-finders-account/src/Plugin.php
51e9b054181e917e61e1b9dbe7d6d18e29513fa6b8a21a416e26084cc932d613  english-finders-account/src/Privacy/PrivacyIntegration.php
05a11596d0a22ea2b381291f5409557172871949568697688af455bb76c43797  english-finders-account/src/Profile/AccountType.php
eea93193795c378ab2897792ba0239e858d4468248b0c5cb1221c9d825d33c6e  english-finders-account/src/Profile/Avatar.php
dccffac85d1f0b4d8df6e3b621e5d62508d599c09bb3bc83488221b52f8d4719  english-finders-account/src/Profile/FullName.php
44616967e4cd1e4eebd1619779761deb0b3bcb3b84339d057933ace1a6d43f64  english-finders-account/src/Profile/ProfileRepository.php
b2a7f212ef03106e8f7a09bd6cbf886d6f34b844c696ceadf186342ed3097e33  english-finders-account/src/Profile/ProfileSaveHandler.php
1d3470d31e30b31bd8c7763ca3e537034775708287c150b691c4c9fdc9c50859  english-finders-account/src/Progress/ProgressController.php
fa4b4334c96c808bce67f1640ada698ff4e49925dd822c2eff1d10c1654e6547  english-finders-account/src/Security/PublicProfileGuard.php
6286bf8a49627d4d7a2fa297d92c3edf316c9ec4dc675984a02afe64ef087910  english-finders-account/src/Security/RegistrationGate.php
c7f475c44ddffa889ace92aa792fc47eaa55baca09bbdd7e895705933736bbd7  english-finders-account/src/Security/RegistrationThrottle.php
09337253075e2d25a46142e61026e7b9d3d7100387471ef6d5ddca39b862ac43  english-finders-account/src/Security/SuspiciousSignups.php
f2fc486dc360cd960f6b41a94df617da29361bd4e6efcab38d05962b79dc29aa  english-finders-account/src/Security/TurnstileVerifier.php
254c7ba97f0ab8e7740512a26c77ca3fb94ec9b8d02493c915239184dbec48d2  english-finders-account/src/Support/CachePolicy.php
997c57ea8ea5d23902e31ea878a21c71ffb99c0ecd903e66fd357954ae041461  english-finders-account/src/Support/Icons.php
779d02babaefd997f181752f8e43ddbb00cae8c4b169410d68cf72c09300a297  english-finders-account/src/Support/Settings.php
7c86345f9db0abc0fda10b5d45cad9d8191fbca741c9b9bf9df4c9dc82dc24ed  english-finders-account/src/Support/Toolbar.php
ab4331353b208de8c3c98a20ddbe972b49a5ce245c40bf8bb989309a830b3f0d  english-finders-account/src/Support/Urls.php
1376bcc11375f114e82264abe4e4f8a36cd8152433d7ca9660a952120d100b10  english-finders-account/templates/admin/pro-settings.php
00369f96aa5b4887aebe19b54cd172dd347b8b5fad42fddc966418dc3f2c0122  english-finders-account/templates/admin/suspicious-signups.php
70621e66f3b5cb2c9bf2a008d0303c1754fe36f79083b0d7359e64d43ce47d62  english-finders-account/templates/public/account-links.php
82629c0a43e04cd99aeee1a515bcfceecc9cc758988d16bd3e4d9d8d9be35f60  english-finders-account/templates/public/account-shell.php
88503a31564ebf095ae8d17d01d7adb1b0ca2a9b0739d75c210ed3fa53662955  english-finders-account/templates/public/auth-page.php
cbc2b4b4289b9bdeca68e567400229e3582cddcfafb7bf1a6e84bc4d09efa74d  english-finders-account/templates/public/certificate-page.php
cb6666d6b427ffdf62b3cee94b485ab0a2472e7a6418ebcca6905be7ecfb608d  english-finders-account/templates/public/certificates-section.php
8be9c1c9b22b17abb3483bbe843d42843e2e6ff05834bddc2e8da68dc6371f7a  english-finders-account/templates/public/home-section.php
d7637c841696b00370ee145f6afd8ba87ca446cd61c28ff6097ace771d059ef6  english-finders-account/templates/public/leaderboard-section.php
24d62f7757babbe635c04ad21f10c708fca33bb2280369cff49b3f79f8040dcb  english-finders-account/templates/public/level-section.php
bde009dd0ac76074f3a47822bb439b8480f7b1064925f880051c40d4f11076bc  english-finders-account/templates/public/library-section.php
09b764f086565000c7aa2c1390a636414f21d61b95625d5e27d79a0b60cd68b9  english-finders-account/templates/public/login-register.php
45341cf31efeed93886fdb9fbf6a4fb968041022c7016bf57ea7116d3412ae2b  english-finders-account/templates/public/membership-section.php
83fd7ba4fa770cbe59eb33887693981ab1d9548300f1bbcee67dd40c29a29418  english-finders-account/templates/public/mistakes-section.php
5e75dc5f95dac125b9170f09757ad0f34f9d52e57d98d0bf2fa38e11868511ab  english-finders-account/templates/public/my-account-template.php
ec0d537970b8568f84461fe13f52c44e72fc3c89b849ac9c5c49e4c0d7bfd25e  english-finders-account/templates/public/partials/login-form.php
1d0d9ec4df933aafc194a7a3f4f7676164ad0f34da45a0f1606e646708327389  english-finders-account/templates/public/partials/signup-form.php
e69f42168463c97c1016827a43112919acf83211d7c64072721f9e7d3ccf21e1  english-finders-account/templates/public/pricing.php
1b94d88897bff718e7a689908871fa3255aad0418a23d846c40e1e54b802f408  english-finders-account/templates/public/progress-section.php
9f0469b5ff4bee368dcd77a34c8595224334bb4bd4f9a4a6f2cf7b671730c08e  english-finders-account/templates/public/weekly-top.php
6033dc39e473c138a850371be7d3a8b53fa63c5133998e7cc85064af123940f4  english-finders-account/templates/tutor/login.php
dc6d94645ade17934e78c3e9fbe6cd568120db9ed0956377ceaf18d03cb6c0ce  english-finders-account/uninstall.php
```
