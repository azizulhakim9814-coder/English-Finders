=== English Finders Core ===
Contributors: englishfinders
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.16.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shared data layer and service foundation for the English Finders plugin suite.

== Description ==

English Finders Core is the shared foundation for Word Games Pro and Learning Toolkit Pro. It is not a standalone product: it provides the dictionary data, external provider connections, and entitlement services that the other plugins consume.

Having one owner for the shared data prevents the two plugins from holding separate, drifting copies of the same words, definitions, and CEFR levels.

= What Core provides =

* Ownership of the shared dictionary tables
* A single connection point for external providers (dictionary, text-to-speech, AI)
* Free/paid tier checks and usage metering

= Requirements =

Core must be active before Word Games Pro or Learning Toolkit Pro can run. WordPress 6.5+ enforces this automatically via plugin dependencies.

== Changelog ==

= 1.16.0 =
* Added: an `ai` service for paid text generation, first used by English Finders Study's AI Writing Feedback tool. It uses the existing OpenRouter key and a model chosen in settings (default anthropic/claude-haiku-4.5, about half a US cent per writing check).
* Daily limits: each signed-in learner gets a small free allowance of AI checks per day (default 3), Pro members a larger one (default 30), and a site-wide daily cap (default 300) stops all AI requests for the rest of the day once reached. A request that fails at the provider is refunded.
* Settings -> English Finders Core has a new "AI features" section: an on/off switch (off by default, so no paid calls happen until it is switched on), the model, the three limits, today's usage and a 7-day table of requests and tokens.
* No database migration: limits are stored in user meta and two options.

= 1.15.0 =
* Added: usage counts for every practice tool and game, including visitors who are not signed in. A small inline footer script listens for the shared ef:progress browser event (already fired by English Finders Study tools and Word Games Pro games), adds it up while the page is open, and sends one report when the page is hidden or left (sendBeacon). One request per active page, not one per answer.
* New REST route POST efc/v1/usage (no nonce, since pages are cached long-term). Reports are strictly validated: source must look like efs-* or wgp-*, the kind must be correct, solved or finished, counts are capped at 500, there can be at most 30 pairs per report, the body must be under 4 KB, and the Origin must be this site when present. The route only increments counters.
* **Migration 1.15.0** (`EFC_DB_VERSION` 1.15.0) adds a `usage_daily` table with one row per site day, source and kind (plus a server-side `visit` per source per report). It stores aggregate counts only: no user ID, IP address, cookie or URL. Rolling back is allowed, since nothing in it belongs to a learner.
* New `usage` service (UsageRepository): add(), totals( $days ), daily_visits( $days ).
* New screen, Settings -> English Finders Usage, shows visits, correct answers, solved puzzles and finished rounds per tool for the last 7, 30 or 90 days, plus visits per day.
* Google Analytics 4: when Site Kit's gtag is on the page, the same script also sends ef_engaged (once per tool per page), ef_solved and ef_finished, each with an ef_tool parameter. Individual correct answers are not sent to GA.
* Filter efc_usage_tracking_enabled (bool) turns off both the script and the route.

= 1.14.0 =
* One XP currency: 1 XP per correct answer. Tutor LMS and QSM quizzes now credit quiz_answer_correct per correct answer (was a flat 15 XP per quiz); a quiz with no correct answers records nothing. New event types for Word Games Pro 2.12.15: game_answer_correct (1), game_solved (5), game_finished (1). Lessons (20) and courses (100) unchanged.
* Fixed: Tutor quizzes never earned XP. Core listened to tutor_quiz_finished, which Tutor 4.0.9 fires only when an attempt is finished without answering; a normal submission fires tutor_quiz/attempt_ended. Both are now hooked, plus tutor_quiz_timeout, all crediting an attempt once. An answer an instructor later marks correct (tutor_quiz_review_answer_after) earns its 1 XP then.
* One-off backfill on the first request after updating: Tutor quiz attempts ended since 2026-09-22 that were never credited get their XP (1 per correct answer), dated when the attempt ended. Guarded by the option efc_tutor_quiz_backfill_1140 and per-attempt user meta, so it can never double-credit.
* Daily XP cap per source: Word Games Pro is capped at 200 XP per learner per site day (filter efc_daily_xp_caps). Events past the cap are still recorded with 0 XP (they count for the streak) and "capped" in their metadata. Study tools, lessons, courses and quizzes are not capped.
* My Account no longer shows a streak that has lapsed: stats_for_user() reads a streak whose gap exceeds the learner's freezes as 0 (nothing written). A gap the freezes cover still shows the streak.
* ActivityRecorder::record_event() takes an optional quantity (XP = value x quantity, one event). New record_backdated() and ActivityRepository::xp_for_source_between(); UserStats::with_current_streak(). No schema change.

= 1.13.0 =
* Pro perk: members with an active Pro (or Teacher Pro) plan get their streak freezes topped up to 5 once a calendar month (site time), applied before a gap is judged so it can cover missed days; free members keep the one-time 2. Stored as user meta efc_pro_freeze_month, no schema change. Filter efc_pro_monthly_streak_freezes. UserStats::with_freezes().

= 1.12.0 =

* **Added: course-completion certificates**, for English Finders Account 0.11.0.
* **Migration 1.12.0** (`EFC_DB_VERSION` 1.12.0) adds a `certificates` table. It allows one certificate per learner per course and gives each certificate a unique 12-character code. Adding the table changes no existing data. Rolling back is refused while any certificates exist.
* New `certificates` service (`CertificateRepository`). `issue_for_course()` stores the learner's name and the course title as they are when the course is finished, plus the CEFR level taken from the course title (A1–C2). Issuing is idempotent: a second call returns the same certificate.
* Codes use 31 characters with no look-alikes (no 0/O, 1/I/L), so they are easy to read and type. They are random, so certificates can't be guessed or listed. Lookups are case-insensitive, and malformed codes never reach the database.
* The learner name is the profile's first and last name if set, otherwise the display name. Anything that looks like an email address is cut before the @.
* Tutor's course-completed hook now issues the certificate, straight after recording the course-completion XP.
* `delete_for_user()` supports personal data erasure.
* 23 new test assertions (`tests/test_certificates.php`).

= 1.11.0 =

* **Added: weekly leaderboard queries**, for English Finders Account 0.10.0.
* `ActivityRecorder::current_week()` returns this Monday-to-Sunday week in site time and its UTC bounds.
* `ActivityRecorder::weekly_leaderboard( $optin_meta_key, $limit, $viewer_id, $viewer_opted_in )` returns the top learners by XP earned this week, with equal XP sharing a rank (1, 2, 2, 4). It also returns the number of participants and the viewer's own weekly XP and rank.
* Only users whose opt-in usermeta flag is exactly "1" are ranked or counted. The flag belongs to English Finders Account, which passes its key in. A viewer who hasn't joined sees their own XP but is never ranked.
* **Migration 1.11.0** (`EFC_DB_VERSION` 1.11.0) adds an `occurred_at` index to `activity_events`. The ranking reads every learner's rows for the week by date, and this table gains a row for every correct practice answer. Adding the index changes no data.
* 22 new test assertions (`tests/test_leaderboard.php`), including the Monday boundary in +06:00, ties, and opted-out or never-joined learners. `test_daily_goal.php` and `test_mistakes.php` now check "DB version at least" instead of an exact pin.

= 1.10.0 =

* **Added: daily goal tiers and per-day XP totals**, for English Finders Account 0.9.0's daily goal and streak calendar. No schema change (`EFC_DB_VERSION` stays 1.9.0).
* `Activity\DailyGoal` defines four goal tiers in XP per day: Casual 10, Regular 20 (the default), Serious 30, Intense 50. The goal counts XP, not minutes, because XP is what the site actually records for every activity; nothing measures time spent.
* `ActivityRecorder::daily_totals( $user_id, $days )` returns XP and event counts for each of the last N days, oldest first, with every day present and zeros for inactive days. It uses one grouped query (`ActivityRepository::daily_xp()`) that shifts the stored UTC times into the site timezone first, so a day matches the calendar day the streak itself uses.
* 14 new test assertions (`tests/test_daily_goal.php`), including events either side of local midnight in +06:00.

= 1.9.0 =

* **Added: mistake notebook storage.** New table `wp_efc_mistakes` (migration 1.9.0, `EFC_DB_VERSION` 1.9.0) and a new `mistakes` service (`Mistakes\MistakeRepository`).
* There is one row per user per practice tool per question. A repeat miss is a single `INSERT ... ON DUPLICATE KEY UPDATE` that bumps `times_missed`, refreshes the snapshot and reopens the entry, so two quick answers can't create duplicates.
* Methods: `record_miss()`, `record_correct()` (closes an open entry when the learner later gets the same question right), `resolve()` ("Got it", scoped to the owner's own rows), `open_for_user()`, `counts_for_user()`, `all_for_user()` and `delete_for_user()` for privacy export and erase.
* Snapshots are trimmed to column size, multibyte-safe, rather than rejected. Logged-out (user 0) is ignored.
* 41 new test assertions (`tests/test_mistakes.php`). `test_core_scaffold.php` was updated for the 8th Core table, and `test_level_results.php` now checks "DB version at least 1.8.0" instead of an exact pin.

= 1.8.0 =

* **Added: English Level Test result storage, for Phase A4 (My Level).** New `level_results` table (migration 1.8.0, `EFC_DB_VERSION` 1.8.0) and a new `level_results` service (`Assessment\LevelResultRepository`): `record()`, `history_for_user()`, `latest_for_user()`, `claim()` (attaches an anonymous result to an account by a 32-hex claim token, only while unclaimed and within 30 days), and `delete_for_user()` for privacy erasure. English Finders Study writes results; English Finders Account reads them.
* **Added: `Assessment\LevelScale`**, the shared CEFR scale (PRE-A1, A1-C2): ordering, labels, can-do descriptions, and the "overall = median of skill levels, rounded down" rule, kept in Core so the test and My Level cannot disagree.
* 52 new test assertions (`tests/test_level_results.php`); `test_core_scaffold.php` updated for the seventh Core table and the 1.8.0 stable tag.

= 1.7.4 =

* **Added: `BadgeCatalog::thresholds()`, for Phase A3's visual pass (My Account UI 0.6.0).** Exposes the numeric threshold behind each streak/XP badge (`streak_7` => 7 days, `xp_100` => 100 XP, etc.) as structured data, so a consumer can render a "you're 45/100 of the way to this badge" progress bar without hardcoding a second copy of Core's own award rules. Deliberately omits `first_activity`, which fires on `total_xp > 0 OR current_streak_days > 0` and so has no single clean threshold to show progress against.
* No schema change. 5 new test assertions, full suite 86/86.

= 1.7.3 =

* **Added: `BadgeCatalog::label()` and `::description()`, for Phase A3 (My Account UI).** The catalog previously only knew badge *codes* (`streak_7`, `xp_100`, ...); this adds plain-text display info for each, so a consumer can render a badge list without hardcoding a second copy of the catalog. Falls back to the raw code (for `label()`) or an empty string (for `description()`) on an unrecognised code, rather than erroring.
* No schema change.

= 1.7.2 =

* **Added: Tutor LMS and QSM (Quiz And Survey Master) feed the shared activity log (Phase A2 step 4, the final step of this phase).** New `Integrations` namespace: `TutorIntegration` listens to Tutor's real `tutor_lesson_completed_after`, `tutor_course_complete_after`, and `tutor_quiz_finished` action hooks (confirmed against the live install's actual source, not assumed from documentation); `QsmIntegration` listens to QSM's `qsm_quiz_submitted`. Both are third-party plugins, so this is the first Activity work that hooks in from the outside rather than editing our own plugin's code directly.
* New `course_completed` event type, 100 XP -- a course is a much bigger unit of work than a single lesson (`lesson_completed`, 20 XP), so it gets its own, larger, still-placeholder value.
* **Idempotency via permanent usermeta, not a transient:** lesson and course completion never expire the way a game round does, so each is credited at most once per (user, lesson-or-course) via a `_efc_activity_credited_*` usermeta marker, set only *after* a successful `record_event()` call (not before), so a resolution failure can't silently and permanently block a retry. This closes a real gap found while investigating: Tutor's own `CourseModel::mark_course_as_completed()` has no idempotency guard of its own.
* Quiz attempts (both Tutor's and QSM's) are the exception -- keyed on the attempt/result id, not the quiz id, so a retake is its own creditable unit, matching the same "repeatable activity" philosophy Daily Wordle's unlimited mode already uses.
* Both integrations register unconditionally in `Plugin::boot()`, with no `function_exists()`/`class_exists()` presence gate -- deliberately, since Core boots at `plugins_loaded` priority 5 and a presence check that early risks a false negative if Tutor/QSM initialise their own globals later in their own `plugins_loaded` callback. `add_action()` for a hook that never fires (because the target plugin is inactive) costs nothing.
* 31 new test assertions (`tests/test_integrations.php`), full suite re-run clean: 383 + 31 = 414/414.

= 1.7.1 =

* **Added: a new `quiz_answer_correct` activity event type (Phase A2 step 3), for English Finders Study.** EFS's quiz tools score one question per request with no server-side session concept, unlike Word Games Pro's Daily Wordle -- there is no "quiz_completed" moment to hook. This event fires per correct answer instead, at 1 XP (deliberately smaller than `quiz_completed`'s 15, so a full multi-question session doesn't dwarf a single Wordle win's 10 XP).
* No schema change -- this is a code-only addition to `ActivityRecorder::XP_TABLE`. `EFC_DB_VERSION` stays at 1.7.0.

= 1.7.0 =

* **Added: shared activity log, XP, and streak foundation (Phase A2, step 1 of its build sequence).** New `Activity` namespace: `ActivityRecorder` (the one entry point other plugins should call to record activity -- registered as Core service `activity`), `ActivityRepository` (raw `activity_events`/`user_stats`/`user_badges` access), `StreakCalculator` (pure streak-continuation logic, no database), `BadgeCatalog` (a small fixed 5-badge catalog), and `UserStats` (the derived-state value object, mirroring `Entitlement`'s role for billing).
* Three new tables: `activity_events` (append-only log, one row per XP-worthy action), `user_stats` (one row per user -- total XP, current/longest streak, streak freezes -- derived entirely from the log, never hand-edited), `user_badges` (idempotent awards via a unique `(user_id, badge_code)` key).
* Streak logic is deliberately forgiving (Duolingo-style, per `my-account-design.md`): a short gap covered by available freezes continues the streak rather than resetting it to an all-or-nothing cliff.
* **No emitters are wired up yet.** Word Games Pro, English Finders Study, Tutor LMS, and QSM do not call `record_event()` anywhere yet -- this release adds the mechanism only, exactly like Billing/Turnstile shipped dormant before their credentials existed. Confirmed via investigation before this was built: WGP's existing `wuc_analytics`/`wuc_user_data` tables (previously assumed reusable per `my-account-design.md`) turned out to be word-finder tool-usage telemetry, not gameplay completions -- WGP needs a new emission call added, which is a later step in A2's build sequence, not part of this release.
* Full spec: the `english-learning-hub` skill's `a2-activity-xp-streak.md`.

= 1.6.0 =

* Added: `PaddlePortalClient` — requests a Paddle Customer Portal session URL (`wp_remote_post()`, same HTTP pattern as `OpenRouterTtsProvider`). Phase A5's billing UI links out to this rather than building a custom change/cancel flow — see `a5-billing-ui.md` for why (Paddle's own portal already handles proration/dunning, which this project hasn't decided policy for itself).
* Added: `TransactionLog` — reads `billing_events` for a same-site transaction history, filtered by `paddle_customer_id`. The exact field path for a transaction's amount is not yet confirmed against a real `transaction.completed` payload (A0's live test used `subscription.created`); extraction is defensive with a documented fallback, called out explicitly rather than assumed correct.
* Added: `paddle_customer_id` column on `billing_events`, indexed, added via `dbDelta()` against the updated `Schema` definition (new `BillingEventsCustomerColumnMigration`, DB v1.6.0) with a backfill for rows written before this column existed. Reversible in `down()` — unlike `BillingTablesMigration`, this column holds nothing that isn't re-derivable from the row's own `payload_json`.
* Added settings: `paddle_api_key` (server-side secret, same wp-config.php-constant-first precedence as `paddle_webhook_secret` via `EFC_PADDLE_API_KEY`), `paddle_client_side_token` (not a secret — Paddle's client-side tokens are designed for browser exposure), `paddle_environment` (`sandbox`/`production`, defaults to `sandbox` since that's the only environment this project has actually exercised).
* `PaddleWebhookController` now extracts and stores `data.customer_id` on every logged event, not just entitlement rows.

= 1.5.0 =

* Added: billing/entitlement foundation (Phase A0 of the account-and-billing build; see `billing-spec-a0.md` and `my-account-design.md` in project docs). New `EnglishFindersCore\Billing` namespace: `Entitlement` (a plan/status value object with a `free()` default and `unlocks_pro()`/`is_teacher()` checks), `EntitlementRepository` (`for_user()`, `upsert_from_subscription()`, `mark_status()`, and the unclaimed-row `find_unclaimed_by_customer()`/`claim()` pair for linking a subscription bought before an account existed), `PaddleSignatureVerifier` (pure-PHP HMAC verification of the `Paddle-Signature` header, no WordPress dependency), and `PaddleWebhookController`, which registers `POST /wp-json/efc/v1/paddle-webhook` and turns `subscription.created/updated/canceled/past_due/paused` events into entitlement writes.
* Added: `wp_efc_entitlements` and `wp_efc_billing_events` tables (new `BillingTablesMigration`, DB v1.5.0). One entitlement row per subscriber is the only thing any plugin should read to decide what a user can do — never Paddle's API directly. `billing_events` is a full, indefinitely-kept log of every webhook received; its `paddle_event_id` unique key is what makes webhook redelivery idempotent (a duplicate insert is treated as "already handled", not reprocessed) rather than a race-prone check-then-insert.
* Why Paddle and not Stripe/PayPal: the business operates from Bangladesh, which is outside Stripe's supported-seller countries and where PayPal is not officially operational for business accounts. Paddle is merchant of record (it collects and remits VAT/sales tax itself) and pays out via Payoneer.
* Settings shape only, no UI yet: `paddle_webhook_secret` and `paddle_price_plan_map` are seeded into `efc_settings` for new installs. The webhook secret is better set via the `EFC_PADDLE_WEBHOOK_SECRET` wp-config.php constant instead — same precedence pattern as `EFC_OPENROUTER_KEY` — which this release's webhook controller already checks first. A settings-screen UI for these lands in Phase A5, not this release.
* Not included in this release: the checkout flow, the My Account billing UI, and the price→plan mapping values themselves (the map is empty until Paddle products/prices exist to map). This release is the receiving end only.

= 1.4.5 =

* Added: `AudioResolver::existing()` — a read-only check for an already-generated recording, with no dictionary provider call, no synthesis request, and no generation lock. Added so a consumer that only wants to know "has this word's audio already been resolved" can find out at the cost of one file check, instead of paying for the full provider-then-generate chain in `resolve()` every time just to reach the same store check that lives at the end of it. Requires Word Games Pro 2.12.5, which is the first release to call it.

= 1.4.4 =

* Added: `wuc_word_senses.cefr_level` / `cefr_source` columns (new `SenseCefrColumnsMigration`, DB v1.4.0), so one specific sense of a word (a part of speech) can carry a CEFR level that differs from the word-level tag `wuc_words.cefr_level` already carried. "Shed" the garden building and "to shed" light on something are the same headword and very different levels; the word-level column could only ever pick one.
* Seeded with 57 `(word, part_of_speech, level)` pairs, cross-checked against the Octanove C1/C2 vocabulary profile (CC BY-SA 4.0) and against this dictionary's own stored sense definitions per part of speech — a pair was seeded only when every stored sense under that part of speech was judged consistent with the tagged level, ruling out the same part of speech mixing a basic and an advanced meaning.
* `WordRepository::senses_for_word_id()` now selects the new columns; a sense with no override returns empty strings for both, same as before this release.

= 1.4.3 =

* Fixed: `WordRepository::random_by_level()` no longer trusts the imported CEFR dataset's machine-inferred word levels at face value for A1/A2. Words like "unanswerable" and "insignificance" were being served as A1/A2 vocabulary — 40% of inferred-A1 and 49% of inferred-A2 words in the dictionary are 9+ letters, far past the 5-7 letters typical of verified beginner vocabulary. Inferred words at A1/A2 are now capped by length; independently verified words are unaffected, as are B1 and above.
* This is the shared method behind Word Games Pro's Word Practice and every English Finders Study quiz tool (Vocabulary Quiz, Definition Match, Spelling Quiz, Pronunciation Practice) that draws distractors or subjects from a specific CEFR band.

= 1.4.2 =

* Added the `efc_cefr_import_applied` action, fired after a real (non-dry-run) CEFR import writes to the dictionary. A dependent plugin caching a derived view of CEFR coverage — which levels exist at all, per-level counts — has no way to know the ground truth just changed underneath it without this. Word Games Pro's level-selector cache was stale for up to a day after the import that first added C2 coverage before this hook existed to fix it.

= 1.4.1 =

* Added a method for fetching random words at a CEFR level with usable definitions, so practice tools in English Finders Study can build activities without querying the dictionary tables themselves.

= 1.4.0 =

* Core now owns the dictionary access layer: the cache, word helpers and word repository moved here from Word Games Pro, so Learning Toolkit Pro can read the dictionary through the same code rather than a second copy of it.
* Word Games Pro keeps its original class names as thin subclasses, so none of its existing code had to change.
* Requires Word Games Pro 2.1.35 or later. Older versions carry their own copies and will conflict.

= 1.3.1 =

* Added a level page preview to the settings screen. It reports how many words exist for each CEFR level and word length combination, so you can see which pages are worth generating before any are created. Nothing is written and no pages are made.
* Adjustable minimum: combinations below it are marked as too thin to publish, since a page with a handful of words ranks for nothing and weakens the section around it.
* Also reports level-only totals, for comparing a few broad pages against many narrow ones.

= 1.3.0 =

* Added CEFR level support. Dictionary words can now carry a difficulty level from A1 to C2, stored alongside a record of whether that level was hand-assigned by researchers or estimated from word frequency.
* Includes a bundled dataset of 51,078 words: 7,035 hand-assigned (CEFR-J, via the MIT-licensed Words-CEFR-Dataset) and 44,043 estimated.
* Words the source data could not place confidently are deliberately excluded rather than given a guessed level. In the raw dataset 67.5% of entries sat at the maximum value, which the algorithm returns when frequency data is insufficient — importing those would have labelled two thirds of the dictionary C2 on no evidence.
* Settings > English Finders Core now has a "Preview coverage" button that reports how many of your words would receive a level, without writing anything, plus an "Import levels" button to apply them.
* The import only updates existing dictionary words. It never adds new ones.

= 1.2.4 =

* Generated audio now reports which accent it produced, so pronunciation buttons can be labelled accurately instead of inheriting the label of the recorded variant they replaced.
* Voices beginning af_/am_ are labelled US and bf_/bm_ are labelled UK; any other naming scheme gets a neutral label rather than a guessed accent.
* Requires Word Games Pro 2.1.23 or later to take effect.

= 1.2.3 =

* Fixed audio generation failing with "Model does not exist". OpenRouter's documentation still shows openai/ speech models, but their live catalogue contains none — every request for one is rejected. The default is now hexgrad/kokoro-82m, which does exist and costs roughly 40 US cents per 100,000 words.
* The voice field is no longer a fixed list of OpenAI voice names, which were invalid on every available model. It now accepts any model-specific voice, with suggestions for the default model's American and British voices.
* Default voice is af_bella (American female). British options include bf_emma and bm_george.

= 1.2.2 =

* Added a Diagnostics section to the settings screen with a "Test speech provider" button. It makes one real request and shows exactly what came back, including the provider's own error message, instead of failing silently.
* Diagnostics also check whether generated audio is enabled, whether an API key is present, and whether the audio folder is writable — an unwritable folder produces the same visible symptom as a rejected key.

= 1.2.1 =

* Added a settings screen at Settings > English Finders Core: switch generated audio on or off, enter the OpenRouter API key, choose a voice, see how much generated audio is stored, and delete it.
* Previously these settings existed but had no interface, which meant the audio feature could not be switched on without editing the database.

= 1.2.0 =

* Added text-to-speech audio generation, fixing pronunciation audio site-wide.
* The Free Dictionary API advertises recorded audio for most words, but its media host has served none of it for a long time (a documented, ongoing failure). Core now verifies that a recorded URL actually resolves before offering it.
* When no recording plays, speech is generated once via OpenRouter, stored as a static MP3 in the uploads directory, and served directly thereafter. A word is paid for once, ever; repeat plays never reach PHP or the provider.
* Disabled by default. Requires an OpenRouter API key, entered in settings or defined as EFC_OPENROUTER_KEY in wp-config.php.
* Requires Word Games Pro 2.1.20 or later to take effect.
* Fixes the plugin header version, which incorrectly reported 1.0.0 in the 1.1.0 and 1.2.0 builds. WordPress reads the header rather than the internal constant, so both releases appeared as 1.0.0 in the plugins list and were not recognised as updates. Runtime behaviour was unaffected, since the version checks used the internal constant, which was correct.

= 1.1.0 =

* Core now owns the six shared dictionary tables (words, word_grams, word_senses, dictionary_packs, import_jobs, import_job_words).
* No data was moved, copied or renamed. The tables keep their existing names and contents; Core simply becomes the code that defines and migrates them.
* Requires Word Games Pro 2.1.16 or later, which relinquishes ownership of the same tables.

= 1.0.0 =

* Initial scaffold: plugin bootstrap, PSR-4 autoloader, service container, own migration ledger and runner, activation/deactivation handling with multisite support, opt-in uninstall, and the public API facade for consumer plugins.
* No dictionary tables moved yet — the data migration is a separate, independently verified step.
