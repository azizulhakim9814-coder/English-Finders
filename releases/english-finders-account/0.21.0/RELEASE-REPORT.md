# English Finders Account 0.21.0: release report

**Released:** 2026-09-29 · **Previous:** 0.20.0 · **Deployed to:** englishfinders.com

Part of Recommendation 6a (owner's decision: the Pro benefit is a larger AI writing allowance).

## What changed

- **`ProOffer::ai_allowances()`** returns the free and Pro daily AI checks from Core's settings.
  - It returns `null` while AI is unavailable (Core older than 1.16.0, or AI switched off), so no plan ever promises a feature that is off.
- **Pricing page** (`[efa_pricing]`): while AI is on, both plans list "N AI writing checks a day". The Free plan now says "9 practice tools".
- **My Account → Membership:** the Pro pitch adds "You also get N AI writing checks a day." while AI is on.
- **Mistakes notebook:** `writing-feedback` entries are labelled "AI Writing Feedback" and link to `/writing-feedback/`.

## Verification

| Check | Result |
|---|---|
| `php -l` on every changed file | pass |
| phpcs (WordPress) | only pre-existing findings in the touched files |
| Local dry run against `git archive HEAD` | identical to repo |
| Live: every file after deploy (80 files, combined SHA-256) | identical to repo |
| Live: `ai_allowances()` with AI off | `null`, so nothing about AI is shown yet |

## Deploy notes

- The live 0.20.0 folder was backed up first to `~/domains/englishfinders.com/ef-backups/english-finders-account-0.20.0-20260929-092700/`.
- Exact-match edits in three steps (classes, then templates, then readme + main file), each hash-checked before writing.

## Rollback

Copy the backup folder over `wp-content/plugins/english-finders-account/`.

## Artifacts

The source zip is identical to the production zip: this plugin has no `tests/` folder.

| File | SHA-256 |
|---|---|
| `english-finders-account-0.21.0.zip` | `6c44150ce7a8d2c797204457fcbfe7cf6a26c36c473269ac522cf9ad8c61d2c3` |
| `english-finders-account-0.21.0-source.zip` | `6c44150ce7a8d2c797204457fcbfe7cf6a26c36c473269ac522cf9ad8c61d2c3` |
| `english-finders-account-0.20.0-to-0.21.0.patch` | `555973e6fe7d38b637b1a57f0bee41dae71bc87d35959bb35ae8a0a7ce990d15` |
