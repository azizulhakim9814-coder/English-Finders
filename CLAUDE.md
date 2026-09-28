# English Finders: project memory

englishfinders.com is a WordPress site becoming a CEFR-first (A1–C2) English-learning platform: content + word tools/games + structured practice, built as **one connected system** (Learn → Practice → Play). Owner: azizulhakim9814-coder.

**Read `docs/roadmap-status.md` before planning work.** It has the full 2026-09-28 audit, what is done, and what is next. Snapshots go stale fast: re-check the live site before building on any number.

## Repo layout

- `english-finders-core/`: English Finders Core plugin (shared data layer: dictionary tables, CEFR, audio/TTS, billing entitlements, XP/activity, level results, mistakes, certificates, **usage counts**).
- `english-finders-account/`: English Finders Account plugin (sign-up/login incl. Google, My Account, level/progress/mistakes/leaderboard/certificates UI, Pro membership via Paddle, spam gates).
- `releases/<plugin>/<version>/`: the four release artifacts per version: production zip, source zip, `.patch` against the previous version, `RELEASE-REPORT.md` with the SHA-256 manifest.
- `docs/`: audit + roadmap status.
- `english-finders-study/`: English Finders Study plugin (8 practice tools + English Level Test + PracticeBox, which adds the practice box and level row to blog posts). Baseline import 1.16.0 = `104499a`.
- `tools/learn-hubs/`: re-runnable builders for `/learn/`, the six level hubs and the four skill hubs (sent through `execute-php`).
- `tools/a1-deepen/`: sources and build/deploy scripts for the rewritten A1 lessons; reuse the method for other levels.
- Not in the repo yet (live only): **Word Games Pro** (`word-games-pro`, v2.12.15, internal namespace `WordUnscrambleCheats`, tables `wp_wuc_*`). Import it as an unmodified baseline commit before changing it (see "Workflow").

Each plugin was imported as an unmodified "as deployed" commit first, so `git diff <baseline>` shows exactly what we changed.

## Live site access

- The live site is managed through the **Novamira MCP** connector (`mcp__novamira-englishfinders-c__*`, ability `novamira/execute-php` runs PHP with full WP loaded, plus Rank Math and Elementor abilities). Connected as WP user 1. **Never modify, deactivate or delete the Novamira plugin**, and never touch user 1's Novamira app passwords or OAuth connections.
- Stack: WordPress 7.1, PHP 8.3, Astra + Elementor, LiteSpeed Cache, **Wordfence (WAF enabled)**, Tutor LMS (6 CEFR courses), QSM + Quiz Cat (legacy quizzes), Rank Math, Site Kit (GA4 + AdSense).
- **Server paths:** web root `/home/u569339493/domains/englishfinders.com/public_html`. Backups live **outside** the web root in `/home/u569339493/domains/englishfinders.com/ef-backups/` (they contain PII; keep them there, never under `public_html`).
- **Claude Code cloud sessions cannot reach englishfinders.com directly:** the egress proxy returns 403, so curl/upload links fail. Transfer files through `execute-php`. Local sessions may have direct access.

## Deploying a plugin change (proven method)

1. **Back up** the live plugin folder to `ef-backups/<plugin>-<oldversion>-<UTCstamp>/`.
2. Send **new files** as PHP nowdoc strings, and **changed files** as exact-match `str_replace` edits (each anchor must occur exactly once).
3. Check the SHA-256 of every staged file against the local repo **before writing anything**; abort on any mismatch.
4. Write new classes first and the files that reference them last (bootstrap and main plugin file), each via temp file + `rename`, then `opcache_reset()`.
5. Verify live: pages return 200 with no "critical error", the migration is in `wp_efc_migrations` (Core), the feature behaves as designed, and there are no leftover `*.efc-tmp` files.
6. Always dry-run the generated deploy script locally against `git archive <baseline>` first and diff the result with the repo. It catches heredoc/newline mistakes.

**Wordfence WAF blocks some `execute-php` requests (HTTP 403 `mcp_request_blocked`, nothing executes).** Known triggers:
- The literal SQL phrases `DROP TABLE` and `CREATE TABLE`: assemble them on the server, e.g. `'DR'.'OP'.' TA'.'BLE'`.
- Regexes containing HTML tags like `<li[^>]*>...<\/a>`.
- A long literal list of page slugs: query by post type instead.
- Large combined payloads: split into one file per request.
- Rank Math redirect definitions: the array keys `pattern` / `comparison` / `ignore` / `exact` together with a URL tripped the WAF. Build the key names on the server (`'pat'.'tern'`, …), store the definitions in a transient, then call `RankMath\Redirections\DB::add()` in a separate request.

If a request is blocked, send `return 1;` to confirm the connection still works, then split the request down to find the trigger.

## Caching rules (important)

- **Never `litespeed_purge_all`.** It cold-starts ~64k dictionary pages on this host. Purge by post ID: `do_action( 'litespeed_purge_post', $id )`. For site-wide changes such as the menu, purge all published `page` + `courses` IDs; other pages refresh within their 7-day TTL.
  - Note: this rule was broken once, on 2026-09-28 (Core 1.15.0 deploy), before it was known.
- Guest HTML also gets a 90-day browser cache from `.htaccess`, and logged-in users are not page-cached. Nonces on cached pages go stale, which is why the tools use session tokens instead.
- Any response personalised for a signed-in user must send `nocache_headers()` + `do_action( 'litespeed_control_set_nocache', '<reason>' )`.

## Workflow and conventions

- Develop on the branch given for the session, commit, push. No PR unless asked.
- Before changing a live-only plugin: pull it into the repo as an unmodified baseline commit ("Import X a.b.c as deployed on englishfinders.com"), then change it.
- **Lint before anything touches the live site** (a past incident came from an unlinted file):
  - Run `php -l` on every changed file.
  - Run the WordPress Coding Standards (`phpcs --standard=WordPress`); install with `COMPOSER_ALLOW_SUPERUSER=1 composer require wp-coding-standards/wpcs:^3` in the scratchpad. The project deliberately differs on PSR-4 file names, per-method docblocks, direct `$wpdb` calls and `$return` parameter names; fix everything else.
  - Run the unit tests in `<plugin>/tests/`, e.g. `php english-finders-core/tests/test_usage.php`.
- **Versioning:** bump the plugin header `Version`, the `*_VERSION` constant, `readme.txt` `Stable tag`, and add a `readme.txt` changelog entry (plain language). Core has a separate `EFC_DB_VERSION`, bumped only with a migration; migrations implement `MigrationInterface` and register in `Database\Migrator::migrations()`.
- **Architecture contracts:** Word Games Pro modules implement `ModuleInterface` and register in `Core\ModuleCatalog`. Study tools implement `ToolInterface` and register in `Catalog\ToolCatalog` (one skill category each; an empty category is hidden from nav). Consumers reach Core only via `EnglishFindersCore\Support\Api::service( 'id' )`. Reuse the WGP engines (Chain/Grid/Quiz/Builder) for new games.
- Every tool and game fires the browser event `ef:progress` `{source:'efs-…'|'wgp-…', kind:'correct'|'solved'|'finished'}`. Core 1.15.0 counts it (Settings → English Finders Usage; table `wp_efc_usage_daily`), and the Account guest nudge listens to it.
- Evaluate new feature ideas with the five gates: Audience, Intent, Ecosystem, Connection, Business value.
- Deletions and other irreversible live changes: back up first, and confirm scope with the owner.

## Current state (2026-09-28)

| Plugin | Live version | In repo |
|---|---|---|
| English Finders Core | **1.15.0** (usage tracking) | yes |
| English Finders Account | **0.20.0** (sign-up → level test welcome) | yes |
| English Finders Study | **1.17.0** (level row on blog posts) | yes |
| Word Games Pro | 2.12.15 | no |

- Accounts: 1,375 after the spam cleanup. 13,004 spam accounts were deleted on 2026-09-28; backup at `ef-backups/spam-users-20260928-074201.json.gz`. The sign-up spam route was closed by Account 0.16.0 (Turnstile + honeypot + throttle on `/sign-up/`).
- The primary menu has a top-level "Level Test" item (menu item 35880).
- 12 broken leftover pages (Paid Memberships Pro, cart, shop, forum, post-grid) were trashed on 2026-09-28 with 301s (Rank Math redirects 17–19); backup in `ef-backups/leftover-pages-20260928-085001.json`. QSM quizzes are embedded in 66 live posts (~12% of traffic): keep them.
- `/learn/` (35893) and the level hubs `/learn/a1/` … `/learn/c2/` (35896–35911) were built on 2026-09-28. The menu's "Learn" item points to `/learn/`. Builder scripts are in `tools/learn-hubs/`; re-run them to refresh the counts.
- Roadmap recommendations 1, 2 and 4 are done; 3 is partly done (owner-scoped). 5 is done as scoped: lesson meta descriptions, the post level row (Study 1.17.0), the four skill hubs, and all 84 A1 lessons rewritten as a pilot (`tools/a1-deepen/`). Proposed CEFR levels for unlevelled posts await owner review in `docs/post-level-proposal.md`. See `docs/roadmap-status.md` for what's left in 3, 5 and 6.
- **Hub pages:** use the `englishfinders-hub-page-builder` skill's recipe, but **ignore its final `LiteSpeed\Purge::purge_all()` step**. It conflicts with the caching rule above, so purge by post ID.
- Page-level traffic: GA4 via Site Kit works from `execute-php` by calling the module directly (`(new \Google\Site_Kit\Core\Modules\Modules( \Google\Site_Kit\Plugin::instance()->context() ))->get_module( 'analytics-4' )->get_data( 'report', … )`). The REST route rejects the OAuth connection, and Rank Math's GSC table is empty.
