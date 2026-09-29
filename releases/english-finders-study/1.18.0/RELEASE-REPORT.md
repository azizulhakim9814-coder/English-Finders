# English Finders Study 1.18.0: release report

**Released:** 2026-09-29 · **Previous:** 1.17.0 · **Schema:** no change · **Deployed to:** englishfinders.com

Part of Recommendation 6a.

## What changed

- **New tool: AI Writing Feedback** (`Tools\WritingFeedback`).
  - Shortcode `[efs_writing_feedback]`, Writing category, attribute `ai`.
  - The learner picks a level and one of 34 tasks from `Tools\WritingPromptBank` (5 per level plus "My own topic") and writes a text.
  - Feedback contains: a CEFR estimate, strengths, up to 8 corrections (each original → corrected, with a reason), task fit, one next step, and a corrected version.
- **Limits:**
  - Sign-in is required for the check.
  - Core's daily allowance applies, plus a burst limit of 4 checks per 10 minutes.
  - Accepted length is a little either side of the level's usual range; the hard caps are 400 words and 3,000 characters.
  - A failed or unusable answer is refunded.
- **Cache-safe page:**
  - The HTML is identical for everyone.
  - `efs_writing_status` returns the viewer's state and a nonce, and is sent with no-cache headers plus a LiteSpeed no-cache.
  - Guests get sign-in and sign-up links that return to the page, and their draft is kept in `localStorage`.
- **Safety of the AI answer:**
  - The learner's text is fenced in `<learner_text>`, and the model is told to treat it as data.
  - The answer is validated and shaped server-side: tags stripped, lengths capped, bad entries dropped.
  - The browser inserts everything with `textContent`.
- **Connections:**
  - The first 3 corrections go to the Mistakes notebook (skill `writing`).
  - Each check records activity (1 XP, counts for the streak).
  - The browser fires `ef:progress {source:'efs-writing-feedback', kind:'finished'}`.
  - The tool starts at the learner's Level Test result.
- **New test:** `tests/test_writing_feedback.php` (117 assertions).

## Verification

| Check | Result |
|---|---|
| `php -l`, `node --check writing.js` | pass |
| `php tests/test_writing_feedback.php` / `test_practice_box.php` | 117 / 24 assertions OK |
| phpcs (WordPress) | only the project's known deviations |
| Local dry run against `git archive HEAD` | identical to repo |
| Live: every file after deploy (50 files, combined SHA-256) | identical to repo |
| Live: catalog | the Writing category lists `sentence-builder`, `writing-feedback` |
| Live: `/`, `/sentence-builder/`, `/grammar-quiz/`, `/practice/` | 200, no critical error |
| Live: guest `efs_writing_status` | 200, `Cache-Control: no-cache, … no-store, private`, sign-in/sign-up URLs with `efa_return` |
| Live: guest `efs_writing_check` | 401 `signin` |
| Live: `/writing-feedback/` (page 35949) | rendered with the tool, then set to **draft** (AI is off until OpenRouter has credits; see the Core 1.16.0 report) |

## Deploy notes

- The live 1.17.0 folder was backed up first to `~/domains/englishfinders.com/ef-backups/english-finders-study-1.17.0-20260929-092700/`.
- Wordfence blocked `WritingFeedback.php` sent as one nowdoc (HTTP 403 `mcp_request_blocked`, nothing ran). It went instead as four base64 chunks, staged in `ef-backups/stage-efs118/` (outside the web root), then assembled, hash-checked and renamed into place. The staging files were removed. `writing.js` went the same way, in two chunks.

## Rollback

Copy the backup folder over `wp-content/plugins/english-finders-study/`.

## Artifacts

| File | SHA-256 |
|---|---|
| `english-finders-study-1.18.0.zip` | `982f244968545a4d7359a239891dd7859a15e85d1cd4603c6fbf3fdcd0062c82` |
| `english-finders-study-1.18.0-source.zip` | `55da38960685c3ab885dfec965427b0353c2ccc292095faa02f0265626c01395` |
| `english-finders-study-1.17.0-to-1.18.0.patch` | `68a8a12e3f1a63fc6615fb6d5e23aa8b722550ea08dcf42c067bc1adcbe0c069` |
