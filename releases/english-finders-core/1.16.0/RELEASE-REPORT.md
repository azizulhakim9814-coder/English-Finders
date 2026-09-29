# English Finders Core 1.16.0: release report

**Released:** 2026-09-29 · **Previous:** 1.15.0 · **Schema:** no change (`EFC_DB_VERSION` stays 1.15.0) · **Deployed to:** englishfinders.com

Part of Recommendation 6a (AI writing feedback, with a larger daily allowance for Pro).

## What changed

- **New `ai` service** (`Ai\AiService`): the single entry point for paid text generation. Consumers call `Api::service( 'ai' )`, check `is_enabled()`, reserve a request with `quota()->reserve( $user_id )`, call `complete_json( $system, $user )`, and refund on failure.
- **`Ai\OpenRouterTextClient`**: chat completions through OpenRouter.
  - It uses the same key as speech (`EFC_OPENROUTER_KEY` or the stored setting).
  - The model is a setting, defaulting to `anthropic/claude-haiku-4.5`.
  - Temperature is 0.2, the timeout is 45 s, and output is capped at 2,000 tokens.
- **`Ai\AiQuota`**: two daily limits, checked on every request.
  - A per-user allowance: `ai_free_daily`, default 3; `ai_pro_daily`, default 30 (Pro comes from the entitlements service); guests get 0.
  - A site-wide cap, `ai_site_daily_cap`, default 300.
  - Counts reset at midnight site time and live in user meta `efc_ai_daily` and option `efc_ai_site_daily`.
  - A failed request is refunded.
  - `daily_limit_for( bool $pro )` is exposed so pages can say what an account gets.
- **Usage log**: option `efc_ai_usage` records requests, failures and prompt/completion tokens per day, and keeps 60 days.
- **Settings → English Finders Core** has a new "AI features" section:
  - the on/off switch (off by default);
  - the model;
  - the three limits;
  - today's count against the cap;
  - a 7-day usage table.
- **New test:** `tests/test_ai.php` (24 assertions: JSON extraction, quota flow, refunds, day reset, settings overrides, the site cap).

## Verification

| Check | Result |
|---|---|
| `php -l` on every changed file | pass |
| `php tests/test_ai.php` / `php tests/test_usage.php` | 24 / 13 assertions OK |
| phpcs (WordPress) | only the project's known deviations (PSR-4 file names, per-method docblocks) |
| Local dry run of the per-file deploy scripts against `git archive HEAD` | output identical to repo |
| Live: every file after deploy (63 files, combined SHA-256) | identical to repo |
| Live: `/`, `/grammar-quiz/`, `/english-level-test/`, `/my-account/` | 200, no critical error |
| Live: settings screen renders the AI section | yes |
| Live: a real request | **refused by OpenRouter: 402 "Insufficient credits"**. The key's account has no credits. AI was switched back off; see below. |

## State after deploy

`ai_enabled` is **off**. The OpenRouter account behind the key has no credits, so no AI request can succeed until the owner adds some. The newest generated pronunciation file is dated 2026-08-25, which suggests speech generation stopped for the same reason. After credits are added, `tools/writing-feedback/activate.php` makes one test request and switches the feature on only if that request succeeds.

## Deploy notes

- The live 1.15.0 folder was backed up first to `~/domains/englishfinders.com/ef-backups/english-finders-core-1.15.0-20260929-092700/`.
- Order: the three new `src/Ai/` classes; then `Plugin.php` and `Installer.php`; then `SettingsPage.php`; then `readme.txt` and the main file. Each step was hash-checked before writing and written via temp file + rename, followed by `opcache_reset()`.

## Rollback

Copy the backup folder over `wp-content/plugins/english-finders-core/`. With AI off, 1.16.0 behaves like 1.15.0 apart from the new settings section.

## Artifacts

| File | SHA-256 |
|---|---|
| `english-finders-core-1.16.0.zip` | `668e029319e45249396231801f997b3cadf9ded7aed27936f0a581aaca52e7e4` |
| `english-finders-core-1.16.0-source.zip` | `fbb0ee6365a0f71118efe125fc66ec4b2da5cebc7eba4a55bb88260ed49bb34f` |
| `english-finders-core-1.15.0-to-1.16.0.patch` | `654b8001480e135f48567e35e75c52f6385b39421e42b0fd442f738b681160de` |
