# English Finders Study 1.17.0: release report

**Released:** 2026-09-28 · **Previous:** 1.16.0 · **Schema:** no change · **Deployed to:** englishfinders.com

Part of Recommendation 5A (connect blog posts to the Learn system).

## What changed

- **PracticeBox level row.** A post in a CEFR level category gets a "Written for B1 Intermediate learners" row with two links: the level's Learn hub (`/learn/b1/`) and its free Tutor course. The "Not sure of your level?" line is dropped on those posts, because the level is already known.
  - The level comes from `PracticeBox::LEVEL_CATEGORIES`, which maps the six level category slugs to A1…C2. When a post is in more than one level category, the lowest level wins (`level_for()`).
  - The course is the published `courses` post whose title contains the level code as a word. The row is left out entirely if the `/learn/<level>/` page is missing.
- **Compact level-only box.** A levelled post that gets no practice box (it already links a practice tool or the Level Test, or no tool fits its categories) now shows a compact `efs-practice-box--level` box containing only the level row.
- Posts without a level category are unchanged.
- The inline CSS moved to `style()` and is printed once per page.
- **New filter:** `efs_level_links_enabled` (bool, post) turns the level row off.
- **New test:** `tests/test_practice_box.php` (24 assertions).

## Verification

| Check | Result |
|---|---|
| `php -l` on every changed file | pass |
| `php tests/test_practice_box.php` | 24 assertions OK |
| Local dry run of the deploy script against the 1.16.0 baseline (`104499a`) | output identical to repo |
| Live: files hash-checked before the swap | 3/3 match |
| Live: B1 post `/verbs-that-start-with-d/` | 200, full box + "Written for B1 Intermediate learners", links `/learn/b1/` and `/courses/b1-english-course/` |
| Live: A1 post that already links a tool, `/sentences-in-english/` | 200, compact level-only box |
| Live: unlevelled post `/5-letter-words-with-3-vowels/` | 200, unchanged box with the level-test line |
| Fatal errors on the pages checked | none |

After the deploy, LiteSpeed was purged **by post ID** for all 335 published posts.

## Deploy notes

- The live 1.16.0 folder was backed up first to `~/domains/englishfinders.com/ef-backups/english-finders-study-1.16.0-20260928-092749/`.
- `PracticeBox.php` was sent whole; the main file and `readme.txt` were changed with exact-match edits. Everything was hash-checked before anything was written, and each file went in via temp file + rename, followed by `opcache_reset()`.

## Rollback

Copy the backup folder over `wp-content/plugins/english-finders-study/`, or set `add_filter( 'efs_level_links_enabled', '__return_false' )` to hide just the level row.

## Artifacts

| File | SHA-256 |
|---|---|
| `english-finders-study-1.17.0.zip` | `de802c2ecd0d97b91fa5a3f54f6dd3d9f0c905e6c04e6cf6a853d139f02ac3b0` |
| `english-finders-study-1.17.0-source.zip` | `0f16e2af1e899fcbdd5b70583e3e2355af0b658c7eb4cf990cc32bb4eded4f55` |
| `english-finders-study-1.16.0-to-1.17.0.patch` | `2cddf036ed4898d6d5e1aaebb6e1ff60f9dec2516889823e444519845d0561d7` |
