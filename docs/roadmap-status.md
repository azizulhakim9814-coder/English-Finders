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

### 3. Remove / redirect old pages; fix the logged-in menu: **PARTLY DONE 2026-09-28**
Owner chose group A only; the rest were reviewed and deliberately left as they are.

- **Done (group A):** trashed 12 broken pages from removed plugins. Each had 0 views in the last 90 days, and nothing links to them.
  - Paid Memberships Pro pages (8) and `/cart/`, `/shop/` → 301 to `/pricing/` (Rank Math redirect 17)
  - `/forum/` → 301 to `/contact-us/` (redirect 18)
  - `/the-post-grid/` → 301 to `/` (redirect 19)
  - The redirect IDs are also in the option `ef_r3_redirect_ids`.
  - Backup: `ef-backups/leftover-pages-20260928-085001.json`. The pages are in the WordPress trash, and WordPress empties the trash after 30 days.
- **Evidence** (GA4 via Site Kit, last 90 days; 25,239 page views across 2,000 URLs):

  | Group | Pages | Views |
  |---|---|---|
  | Off-topic utilities | JPG/Text to PDF, Image to Text, case converters, Word Counter | 0–3 each |
  | Their hub | `/tools/` | 41 |
  | Not in any menu | Word Search Solver | 145 |
  | Not in any menu | Daily Wordle Unlimited | 144 |
  | Not in any menu | Word Scrambler | 0 |

- **Not done, by owner decision (revisit later):**
  - B: trash the utilities + `/tools/` with 301s to `/word-tools/`
  - C: add Daily Wordle Unlimited to Games, and Word Search Solver + Word Scrambler to Word Tools
  - D: unassign the "Logged In Account Menu". It is **not displayed anywhere**: the Astra header is logo + primary menu + search + an HTML block, so this is cosmetic.
  - Leftover DB data from removed plugins (`wp_pmpro_*`, `wp_um_metadata`, `um_form`/`bp-email`/`buddypress`/Q&A posts, the Woo cart/shop options): left for a later database tidy-up.
- **Keep as is:**
  - `/dashboard/` (Tutor student dashboard, 109 views)
  - `/student-registration/` and `/instructor-registration/` (Tutor settings reference them; they already 302 to `/sign-up/`)
  - `/my-account/`
  - `/quizzes/` (QSM quiz hub, 61 views)
- **QSM is alive, so do not remove it.** 66 posts embed QSM quizzes; those posts had about 2,960 views in 90 days (~12% of site traffic), with 20–120 completed quizzes a month in 2026. Top pages: 50 English grammar quiz (412), types of pronouns (434), types of morphemes (425). Consolidating into Study tools or Tutor would be a content migration project, not a cleanup.
- Rank Math 404 log: only one-off multi-word dictionary lookups (e.g. `dictionary/figure out`); nothing to redirect.

### 4. `/learn/` landing page + one hub per CEFR level: **DONE 2026-09-28**
- **Built** as Elementor pages with the site hub recipe (owner's choice), cards + embedded practice, at `/learn/a1/` … `/learn/c2/`:

  | Page | ID | Contents |
  |---|---|---|
  | `/learn/` | 35893 | Level Test + A1…C2 cards (units/lessons pills); "Learn by skill" grid of the six skill hubs |
  | `/learn/a1/` … `/learn/c2/` | 35896, 35899, 35902, 35905, 35908, 35911 | The level's course, words, articles, Level Test and the 8 practice tools (12 cards); then Grammar Quiz + Vocabulary Quiz embedded, preset to the level (`[efs_grammar_quiz level="B1" breadcrumb="0" schema="0"]` etc.) |

- **Menu:** "Learn" (item 34377) now points to `/learn/` (was `/grammar/`); confirmed after saving. All published pages and courses were purged by ID.
- **Hover CSS:** in Additional CSS between the `EF-LEARN-HUBS-START` and `EF-LEARN-HUBS-END` markers.
- **Verified:**
  - Live HTML: 200s, no fatal errors, correct card counts, order and titles, all titles linked, pagination hidden.
  - Embedded quizzes render with the level pre-selected (select + config), and their scripts load.
  - Local Chromium at 1280/900/390 px: 3/2/1 columns, banner 96 px, no clipping. The 350 px card height reads as 352 only because the local test page lacks the theme's border-box rule.
- **Builder scripts:** `tools/learn-hubs/`. Re-run them to refresh the counts written into the cards after content grows.
- **Follow-ups:**
  - The Learn dropdown is long (22 items); consider trimming it now that `/learn/` exists.
  - Every tool page's level select defaults to "Any level". A `?level=` URL parameter would let the level-hub cards deep-link into the right level, but that needs a Study plugin change.
  - C2 has only 4 articles.

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
