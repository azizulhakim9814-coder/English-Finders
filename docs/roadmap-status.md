# English Learning Hub: audit and roadmap status

Live audit of englishfinders.com on **2026-09-28** (via the Novamira connector), updated as work lands. Numbers are true as of that date; re-check before relying on them.

## What is already built (corrects older roadmap notes)

The older roadmap (English Learning Hub skill: WGP 2.9 / Core 1.4 / Study 1.3) is out of date. Live now:

- **Navigation hubs:** the primary menu is Home · Learn · Level Test · Practice · Games · Word Tools · Blog. Learn holds the skill hubs, the 6 courses and the CEFR word lists.
- **Word Details pages:** an embedded "Practise This Word" quiz (WGP `word-practice` module), a CEFR badge, and a "{Level} Words" card first in Related Tools.
- **English Finders Study:** 8 tools covering all 6 categories:

  | Category | Tools |
  |---|---|
  | Vocabulary | Vocabulary Quiz, Definition Match |
  | Grammar | Grammar Quiz, Error Correction |
  | Reading | Reading Quiz |
  | Writing | Sentence Builder |
  | Spelling | Spelling Quiz |
  | Pronunciation | Pronunciation Practice |

  Also the **English Level Test** (grammar/vocab/reading → CEFR; anonymous results are claimable for 30 days) and **PracticeBox** (a "practise what you read" box on every blog post, linking the matching tool and the Level Test).
- **CEFR courses:** 6 Tutor LMS courses A1–C2, 13 topics each, 399 lessons, 78 quizzes / 780 questions, all free.
- **Accounts** (English Finders Account plugin): sign-up/login incl. Google; My Account with Home "next step" card, My Level, progress/XP/streak/badges, mistakes notebook, weekly leaderboard, certificates, library.
- **Billing:** Paddle in production mode. Pro = ad-free + streak freezes, $2.99/month or $24.99/year. The "Go Pro" promo switch is **off**; 0 subscribers. Paid Memberships Pro, Ultimate Member, BuddyPress and the Q&A plugin are gone (leftover pages and tables remain).
- **AI:** Core's `ai_enabled` is **false** and the AI Engine plugin is **inactive**. No AI feature is live. TTS (OpenRouter) is on.
- **Word Games Pro modules:** Dictionary Search, Definitions, Pronunciation, Audio Pronunciation, Parts of Speech, Examples, Synonyms, Antonyms, Word Details Pages, Word Finder, Advanced Word Finder, Vocabulary, Word Ladder, Word Chain, Pattern Hunt, Hangman Reimagined, Daily Unscramble, Best Word, Word of the Day Challenge, Word Practice, Toolkit Admin, SEO Word Pages.

## Key numbers (2026-09-28)

- **Dictionary:** 64,041 words, 115,302 senses, 423K n-grams.
- **CEFR tags:** 33.8% of words (A1 3,017 · A2 3,783 · B1 5,386 · B2 5,357 · C1 3,275 · C2 844). By source, 7,480 are verified, 13,487 inferred, 688 octanove and 7 AI-assessed. Only 154 of 115K senses have a level.
- **Engagement was tiny** before the tracking work:
  - 7 users had ever logged XP, and there were 5 Level Test results (mostly staff/test accounts).
  - 10 Tutor quiz attempts and 42 lesson completions; 0 enrolments in B1–C1.
  - QSM results are falling: 3,403 in 2025 vs 749 so far in 2026.
- **Rank Math site score:** 61 (focus keywords missing on 258 lessons, 75 quizzes and 6 courses). 248 posts have no internal links.

## Recommendations: status

### 1. Stop spam sign-ups + add usage tracking: **DONE 2026-09-28**
- The spam route (old Tutor/WP registration forms) had already been closed by Account 0.16.0. Verified live: all routes 302/303 to `/sign-up/`, which has Turnstile + honeypot.
- Deleted 13,004 spam accounts: no real-use signal plus a non-webmail domain. Kept the 1,230 unused webmail accounts and everyone with activity. 14,379 → 1,375 users. Backup: `ef-backups/spam-users-20260928-074201.json.gz`.
- **Core 1.15.0:** per-tool usage counts for everyone, including guests (`ef:progress` → one sendBeacon per page → `wp_efc_usage_daily`), plus GA4 events `ef_engaged` / `ef_solved` / `ef_finished` with an `ef_tool` parameter. Report at Settings → English Finders Usage.
- **Owner to-do:** register `ef_tool` as an event-scoped custom dimension in GA4.
- **Follow-up:** check the usage report after 1–2 weeks and let the data drive which tools get invested in.

### 2. First-visit path (sign-up → Level Test → course): **DONE 2026-09-28**
- **Menu:** top-level "Level Test" (item 35880).
- **Account 0.20.0:**
  - After sign-up (form or Google), a learner with no level result goes to the Level Test with a welcome banner, or back to the page they came from with a banner linking the test.
  - Teachers, and learners with a claimed result, go to My Account (its Home card names the course).
- **Already existed:** the test result links the matching course; anonymous results are claimed on login/registration; My Account Home suggests the test and then the course.
- **Possible follow-ups:**
  - Link the Level Test from Word Details pages and practice tool pages.
  - Give the menu item a visual accent (CSS class `ef-nav-level-test`).
  - Check sign-up → test → enrolment conversion in the usage and level data.

### 3. Remove / redirect old pages; fix the logged-in menu: TODO
- **Paid Memberships Pro pages** (plugin removed, raw shortcodes): `/membership-account/` (+ billing, cancel, orders, your-profile), `/membership-checkout/` (+ confirmation), `/membership-levels/`.
- **WooCommerce pages** (plugin inactive): `/cart/`, `/shop/`. `/my-account/` must stay: the Account plugin uses it, although its content is still `[woocommerce_my_account]`.
- **Other leftovers:**
  - Forum `/forum/` (Q&A plugin gone)
  - Tutor `/dashboard/`; the Tutor student/instructor registration pages already redirect to `/sign-up/`
  - `um_form` / `bp-email` posts
  - Leftover tables `wp_pmpro_*`, `wp_um_metadata`
- **Off-audience utilities** (run through the five gates: keep / redirect / remove): Image to Text, JPG to PDF, Text to PDF, case converters, Word Counter, `/tools/`, `/the-post-grid/`.
- **Logged-in account menu** (`loggedin_account_menu`) is stale: it points to a draft page (`?page_id=3204`) and lists Linguistics/IELTS; there is no Practice or Games. Check whether the theme still shows it.
- Pages that are published but not linked from any menu: Daily Wordle Unlimited, Word Search Solver, Word Scrambler.
- **Three quiz systems:** QSM (49 quizzes, declining; top ones are Complex Sentences Quiz and 50 Mixed English Grammar Quiz), Quiz Cat (13), and Tutor quizzes + Study tools. Decide on consolidation.
- Use 301 redirects (Rank Math redirections) for anything with traffic.

### 4. `/learn/` landing page + one hub per CEFR level: TODO
- "Learn" in the menu currently points to `/grammar/`. There is no `/learn/` page.
- Build per-level hubs (A1…C2), each combining:
  - the level's course
  - its CEFR word list (`/word-finder/a1-words/`…)
  - its level category posts (categories under parent 1668: A1 59, A2 74, B1 72, B2 18, C1 20, C2 4 posts)
  - level-appropriate practice
- Use the `englishfinders-hub-page-builder` skill (Elementor). Ask which page-building approach to use before building.

### 5. Deepen content: TODO
- 126 of 399 lessons are under 1,500 characters. Every level except C2 has 21–31 of them.
- No lesson has audio or video (no listening material).
- **Blog volume at B2–C2 is thin:** 18 / 20 / 4 posts. 88 posts have no CEFR level category.
- **Hub pages:**
  - Reading, Writing, Speaking and Listening are near-empty (Elementor only, about 3.8K of data).
  - Grammar and Vocabulary were last edited in March 2025; IELTS in 2023.
- 248 posts have no internal links.
- **Rank Math:** set focus keywords and titles for lessons, quizzes and courses.
- Relevant skills: `cefr-course-builder`, `englishfinders-content-optimizer`.

### 6. New features (after 1–5): TODO
- **AI writing feedback:** passes all five gates. AI is currently off, and the OpenRouter key exists for TTS.
- **Listening/Speaking:** the tool catalog has no listening category; adding one is a product decision (`ToolCatalog::CATEGORIES`).
- **Teacher tools:** no tool carries the `teacher` attribute yet, so a teacher hub would be empty.
- **Question banks** are small: Grammar 18 topics, Reading 24, Error Correction 21, Sentence Builder 10.
- **Pro:** decide what learning features to gate (certificates? AI feedback?) before switching on the promo.
- **CEFR data:** raise verified coverage and sense-level tagging. Link Word Details pages to the level's course and the Level Test.
- **Curated daily Wordle puzzles** ran out on 2026-08-13. The game falls back to random dictionary words; add more curated puzzles or accept the fallback.

## Competitors (background)

7ESL is the only real strategic threat (full CEFR spine, 40–80 tools, XP games, tutor marketplace, AI app). Its word-list pages embed a matching quiz, a pattern we already copied on Word Details pages. word.tips and wordunscrambler.net are utility/ad models with no learning layer and are not worth imitating as a business.
