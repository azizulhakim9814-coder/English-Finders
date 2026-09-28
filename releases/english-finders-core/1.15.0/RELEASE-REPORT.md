# English Finders Core 1.15.0: release report

**Released:** 2026-09-28 · **Previous:** 1.14.0 · **DB version:** 1.12.0 → 1.15.0 · **Deployed to:** englishfinders.com

## What changed

Usage counts for every practice tool and game, including visitors who are not signed in. Before this release the site could not tell which tools are used: member XP only covers signed-in users, and Word Games Pro's analytics table only logs searches.

- **Browser:** an inline footer script (under 1 KB) listens for the `ef:progress` event that Study tools and Word Games Pro games already fire. It adds the events up and sends **one** `sendBeacon` report when the page is hidden or closed.
- **Server:** `POST /wp-json/efc/v1/usage` validates the report and adds it to `wp_efc_usage_daily` (day × source × kind → count). Only aggregate counts are stored: no user ID, IP address, cookie or URL.
- **Admin:** a new **Settings → English Finders Usage** screen shows visits, correct answers, solved puzzles and finished rounds per tool (7, 30 or 90 days), plus visits per day.
- **GA4:** the same script calls Site Kit's `gtag` with `ef_engaged` (once per tool per page), `ef_solved` and `ef_finished`, each carrying an `ef_tool` parameter.
- **Kill switch:** the `efc_usage_tracking_enabled` filter.

## Verification

| Check | Result |
|---|---|
| `php -l` on every changed PHP file | pass |
| `tests/test_usage.php` (input validation, 13 assertions) | pass |
| WPCS (WordPress standard) on new files | clean apart from the project-wide conventions the existing Core files also use (PSR-4 file names, per-method docblocks, direct DB calls) |
| Headless Chromium: real `ef:progress` events → beacon | 1 beacon with correct totals; invalid events dropped; no duplicate beacon; 4 GA4 calls as designed |
| Live: files hash-checked before and after the swap | 9/9 match |
| Live: migration 1.15.0 in ledger, table created | pass |
| Live: `/`, `/grammar-quiz/`, `/daily-unscramble/`, `/dictionary/apple/`, `/word-ladder/` | 200, script present, no fatal errors |
| Live: endpoint | valid → 204, bad source → 400, foreign Origin → 403, non-JSON → 400 |
| Live: admin screen renders | pass |
| LiteSpeed cache purged after deploy | done |

## Deploy notes

- A backup of the live 1.14.0 plugin folder was taken first, to `~/domains/englishfinders.com/ef-backups/english-finders-core-1.14.0-20260928-075329/` (outside `public_html`).
- Direct upload was blocked by the build environment's proxy, and one large combined request was blocked by the Wordfence WAF (nothing was written). Files were then staged one at a time in `wp-content/upgrade/`, checked against the SHA-256 values below, and swapped in with new classes first, so no request could see a half-deployed plugin. The staging folder has been removed.

## Rollback

Copy the backup folder above back over `wp-content/plugins/english-finders-core/`. The `usage_daily` table can stay (1.14.0 ignores it), or it can be removed with the migration's `down()`.

## Artifacts

| File | SHA-256 |
|---|---|
| `english-finders-core-1.15.0.zip` | `16e20bdf4f4502dac8d145659339de344138595c41d5034b74be7e0c4b7d199c` |
| `english-finders-core-1.15.0-source.zip` | `ef48303e54f5d8d827cd48244038b173df1847fd3b6a77c5efa59d6710071e82` |
| `english-finders-core-1.14.0-to-1.15.0.patch` | `b6e1751115196b2234a9e68e2dc63ba57eac583869e78ceddc105af819a50036` |

The production zip has no `tests/` folder; the source zip includes it.

## Production file manifest (SHA-256)

```
1834ca0e6881e599a49bd255d33ed5fbd2bf68662c4d9e7087677f71037ec599  english-finders-core/data/cefr-levels-LICENSE.txt
027cebd3ebafdda35eafa3a16aa091a0016024c9bf943501f5cde1b7e73b6172  english-finders-core/data/cefr-levels.csv.gz
645ec28d11a8894dbd18bc5f1cafdc0526650dedd992b8869dce383b01ffd611  english-finders-core/english-finders-core.php
ffb4f3b82d9fadd6928f0abfb17c7c976b117f516d9b0cfe879e3432bf5b55bc  english-finders-core/readme.txt
301c846a0f7b4e2c9b8fbdfe836354cdf435897c10616b2d1fddca849ee0049a  english-finders-core/src/Activity/ActivityRecorder.php
722e26d9bf5a45e530ec3258f794e7aa3022a55c0c0444fd2c48c37da2b10564  english-finders-core/src/Activity/ActivityRepository.php
27a70847fd357b2cb8779797e30125eb573a2356795846edddd8eef63c766f34  english-finders-core/src/Activity/BadgeCatalog.php
c5eccff670a33eb61554cf4998993fc368302c0051e7cd4e9c365764133ba222  english-finders-core/src/Activity/DailyGoal.php
655497febeb7665362c6ee31a7ede915fa3dd8b001b8bb528674ca48e5854265  english-finders-core/src/Activity/StreakCalculator.php
b6c7a145fdb84bb480d7e1ec1074fb6b8e5a86272f2a151fdbb58b92ebb979c6  english-finders-core/src/Activity/UserStats.php
cc8e3a842b44bcae7278495aade97c42e66f2d24011630c81414b830bd24ae00  english-finders-core/src/Admin/SettingsPage.php
d0a38e7d5a9b3c72e44c7e79b4f49b4b4ee7d484b6a083ef0a72e434b6ba0cd4  english-finders-core/src/Admin/UsagePage.php
188bef0d1181c217c803c527e9b75993e8311e974c591880eb1bb291f2700412  english-finders-core/src/Assessment/LevelResult.php
c7a178cb7fe7f873081296667cc52e8509538717f847873534fa72865fba1d5b  english-finders-core/src/Assessment/LevelResultRepository.php
900953b22fa29bf093ad93cddd5b9236707d955b9dbb9fdbc1617884380be438  english-finders-core/src/Assessment/LevelScale.php
63424cbf4992f2cb7a4b98693a2936e408768bd6b7d348d9094b02beff129e6a  english-finders-core/src/Audio/AudioResolver.php
3ee6ac750757fc64cdaab6efa171eed285c53c19ba10fd11233dcac960d1fbd6  english-finders-core/src/Audio/AudioStore.php
ecc97b15993d4f903a6c12598a0d8155226b4d199e4e4201d0bde40054a313b1  english-finders-core/src/Audio/OpenRouterTtsProvider.php
0324e0eda84a79459c58be2a39b3def585beb4e42e186f9a806c2030f946626b  english-finders-core/src/Billing/Entitlement.php
bae02c79fe88fac8a503e357e707b73baf17652ec0dbc496d770db6b87208443  english-finders-core/src/Billing/EntitlementRepository.php
cefff003fc60086b8eb46eceec24081e4681c5211c03ecec54f286d94e21d0da  english-finders-core/src/Billing/PaddlePortalClient.php
690e61e8327d91d611b2f4322c40a61e504c1c6df921c86e954e387bf7c00e8e  english-finders-core/src/Billing/PaddleSignatureVerifier.php
52e5374ec995dc8dcc0891541ce06e47cdd4066ce7c0b5d923a94e0299b24ca1  english-finders-core/src/Billing/PaddleWebhookController.php
b36d6a513208e7acfba74abc77d2cd770709cdf974017d35f09d5add1c615eae  english-finders-core/src/Billing/TransactionLog.php
187368f939d78211182b8ba2336607d997e053bf14aaca80c7afcdcd7b308019  english-finders-core/src/Cefr/CefrImporter.php
33043bf7c0c788a893116bfa360438b1c8a57de22352b50b836b87090abc8a4c  english-finders-core/src/Cefr/LevelPagePreview.php
06c39ca8c25ceb381b6935c830f95aa5c8b338c744442803f7e75c9055267509  english-finders-core/src/Certificates/Certificate.php
45fd42ed70c3fbf62c27d753b77f0aaaad199a89236436cb373b5ea3a599f2c4  english-finders-core/src/Certificates/CertificateRepository.php
a660df05b3716499af59a2ff3c4789735f2402a8eb6ce0ac606614ef3ecb9ca7  english-finders-core/src/Contracts/AudioProviderInterface.php
a351809430f0e66db6b40ad791e810311912eabfb5c7ff493d084a81701d7ebe  english-finders-core/src/Contracts/MigrationInterface.php
5a7030411a9fbd7eeba14dbc79d68cbdbcd0c0b735403516b2fe32896cd0aa89  english-finders-core/src/Core/Activator.php
35d829a964867ac0c982aa0a24c82c0ab0297a2ede480b507966d798f6d2a4a6  english-finders-core/src/Core/Autoloader.php
becf159f03cda7064396ad2f8ed3f6a73772129e970506e70a552e2b949e54e2  english-finders-core/src/Core/Cache.php
10bdbcc39b19c83bb1a3e64b06040e173d2271cf0c48f808c6e1d43c8bbfba9e  english-finders-core/src/Core/Deactivator.php
0088b7c9de68ba613c94f397e7dffc0ff3a9a498c52fc211ab185d0839d39cf6  english-finders-core/src/Database/ActivityOccurredAtIndexMigration.php
3a7791077a871ff7dd48b4af0e98ea74384a8e19a2585186b6561270391e0e12  english-finders-core/src/Database/ActivityTablesMigration.php
0b986de7f7ef74e4005145977babdba703835ce81acbc217abe30fcdbc51bd96  english-finders-core/src/Database/AdoptSharedTablesMigration.php
fa3fb7ded6b4c9c27a4cf8e7d4b1215787cf665ca2146a6b705f3dd742971b36  english-finders-core/src/Database/BillingEventsCustomerColumnMigration.php
70296304c8fbc47b1841c537c7e403a341c1c565f26ddf081f7521bd41ddbb60  english-finders-core/src/Database/BillingTablesMigration.php
5716d93ad255114c855962d5439d73d8f5b7301cb4998acc9cb9e8745e810bd3  english-finders-core/src/Database/CefrColumnsMigration.php
e26d76a0ccdda386a73e90d34f39da8506343a5c199be82ce85532714dd79411  english-finders-core/src/Database/CertificatesTableMigration.php
840dd95f1d53c6311853127e8a184625134e5881ae67b2c7433c85fbe45dbc51  english-finders-core/src/Database/InitialMigration.php
eb05a3ebb0a4e7dd7445defc15da69cd1449aedae1732b34a5b4aeda99ce48f9  english-finders-core/src/Database/Installer.php
af6f32e6fd86c7b43d20536066d3b93dc1f2f50daf023f183e7df19ca52470f7  english-finders-core/src/Database/LevelResultsTableMigration.php
988ee16fa0484962f024e6a8a7bf8556f471d04e8ebcb8c433d197c19a715de5  english-finders-core/src/Database/Migrator.php
e9b5f6e50f746c5ee7f875549406aa86bb576066a662356521c13d84e736c56a  english-finders-core/src/Database/MistakesTableMigration.php
0ebc4c8ea5e0f99ae9e85e68da161d161c031fb162639e65af3d9e9a1f26d862  english-finders-core/src/Database/Schema.php
4a9d0d3c455658d91a881b16baab37646b7baa1cceb0744939f8f31ea8059a95  english-finders-core/src/Database/SenseCefrColumnsMigration.php
268cd2a22b29516ccf9f025243e5252d38477a1b470dfd7c602163389fb7dea3  english-finders-core/src/Database/UsageTableMigration.php
0c53b27669cb0da71e773a660d36f6d1b4cc0fe4e43009337d91ae3e20b19148  english-finders-core/src/Integrations/QsmIntegration.php
a58cc6d3160a3d1bb9633523733f0c97ecb7e746b4fde1fed7ba7a8a994c0eb3  english-finders-core/src/Integrations/TutorIntegration.php
9312bd9836a67bb3857eeae1cf7a4a2d233a6efa02a1351a57c13b1eb1a7c5e2  english-finders-core/src/Mistakes/Mistake.php
3b79ee00d33d0ab2ccd2ad6062d6ba864a77f82aed3ccc9ab787ed34ef73d1a2  english-finders-core/src/Mistakes/MistakeRepository.php
69445f7bedc2d5fc2ff36cacb11501ac79c1b9002e37baba7fc19bd12e80b64a  english-finders-core/src/Models/WordRepository.php
047fe06045b7874642992db17aca362b7fe8c7339103fbe9bb1bf74ddab5921c  english-finders-core/src/Plugin.php
97683c6f2d844b0b8ec5b6faa3a157af1730d36d26dd366f3450c2e6ba5a68e1  english-finders-core/src/Support/Api.php
0fb70cb7a9c581095b6c61d4cedefcd23345309a1a736728306decdd4b5e142a  english-finders-core/src/Support/WordTools.php
6a78836421d614c69094eedf804c9a9cf276f1db784042d079c907343273eba7  english-finders-core/src/Usage/UsageController.php
4cacf125c2aba3c2d6d3553cccfd5fbbad14b2f1373d56ee77b0c5876490bdaa  english-finders-core/src/Usage/UsageRepository.php
84f95b51d7c1644ecb51bea32197d33d1d42250d7a2b9e230de3a2186cfb5d9a  english-finders-core/uninstall.php
```
