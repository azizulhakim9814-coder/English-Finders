=== English Finders Study ===
Contributors: englishfinders
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.17.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Practice tools for English learners and teachers.

== Description ==

English Finders Study provides practice activities across six skills: vocabulary, grammar, reading, writing, spelling and pronunciation.

It is the practice half of the English Finders suite. Word Games Pro answers questions — what a word means, which words fit a pattern, how something is pronounced. Study creates activities with a result: test me, check my writing, generate a worksheet.

Tools are organised by skill. "For teachers" and "Uses AI" are filters that cut across categories rather than categories of their own, so a grammar worksheet for teachers appears in grammar navigation and in the teacher view without being duplicated.

= Requirements =

English Finders Core 1.4.0 or later must be installed and active. Study reads the shared dictionary, CEFR levels and provider connections from Core rather than holding its own copies.

== Changelog ==

= 1.17.0 =
* Blog posts in a CEFR level category (A1 ... C2) now lead into that level. The "Practice what you just read" box gains a "Written for B1 Intermediate learners" row linking the level's Learn hub (/learn/b1/) and its free course. Levelled posts that get no practice box (because they already link a practice tool, or no tool fits) show a compact level-only box instead. Posts without a level category are unchanged and keep the level-test link.
* If a post is in more than one level category, the lowest level is used. The hub link is only shown when the /learn/<level>/ page exists, and the course link only when a published course carries that level code in its title.
* New filter efs_level_links_enabled (bool, WP_Post). New public PracticeBox::level_for( $category_slugs ) and the LEVEL_CATEGORIES / LEVEL_NAMES constants.
* tests/test_practice_box.php (24 assertions): level mapping, unchanged tool rules, and both box variants render well-formed HTML.

= 1.16.0 =
* 468 new practice questions. Per level (A1-C2): Grammar Quiz 42 -> 60, Reading Quiz 24 -> 36 passages, Error Correction 12 -> 36, Sentence Builder 12 -> 36. That is 1,008 practice questions in total (was 540). The Level Test also draws on the grammar and reading banks, so it gets the new items too.
* Shuffled without repeats across sessions and visits: each tool remembers in the learner's browser (localStorage, per tool and level) which items it has already served, so "Start a new session", changing level and coming back later all continue with unseen items. Once every item at a level has been seen, the server starts a new shuffled cycle and flags it with `cycled` so the browser resets its record. A new cycle never opens with the item just shown. Storage access is guarded, so private windows still work, without memory between visits. The seen-list limit (MAX_EXCLUDE) goes from 200 to 400 so an "Any level" pool fits.
* Error Correction: new topics (plurals, verb forms, pronouns, future forms, question forms, gerunds and infinitives, negatives, linking words, inversion). The original item quant-err-7 ("Neither of the answers were correct") was replaced, because that usage is widely accepted. Sentence Builder: new C2 topic "Fronting". Every new sentence has exactly one acceptable word order.
* Tests: counts updated; the Grammar Quiz, Error Correction and Sentence Builder suites now draw an entire A1 pool and check the new-cycle flag and the no-immediate-repeat rule; a source check covers the storage and cycle code in all four tools.

= 1.15.0 =
* Bigger question banks for the English Level Test: 312 new questions, 52 at every CEFR level (A1-C2). Per level: grammar 24 -> 42, reading 6 -> 24 passages, vocabulary 14 -> 30 words. That is 96 questions per level and 576 in total (was 264), so a learner who retakes the test mostly sees new questions. The length and scoring of the test are unchanged.
* The Grammar Quiz and Reading Quiz share the grammar and reading banks, so they get the new items too. Four new reading topics (Psychology, Society, Media, Arts) and one new grammar topic (Inversion, C2 only).
* All 96 new vocabulary words were checked against the live dictionary and are list-verified at exactly the level they are filed under.
* Tests: new ReadingBank checks (unique ids and passages, 24 per level, four distinct options, labelled topics, and every quoted explanation must appear word for word in its passage); counts updated for grammar (252) and vocabulary (30 per level).

= 1.14.0 =
* "Practice what you just read" box at the end of every blog post (ContentPracticeBox), pointing at the practice tool that fits the post's category -- Reading Quiz, Sentence Builder, Pronunciation Practice, Vocabulary Quiz, Grammar Quiz (A1-B1) or Error Correction (grammar posts tagged B2-C2) -- plus the free level test. Not on reviews/gear posts, posts that already link a tool, feeds or excerpts. Filters: efs_practice_box_enabled, efs_practice_box_tool.

= 1.13.1 =
* The 8 practice tools send the shared browser event `ef:progress` (detail: source, kind "correct") when an answer is confirmed correct. English Finders Account 0.17.0 uses it to invite visitors who are not signed in to save their progress. The level test does not send it (it has its own sign-up step).

= 1.13.0 =

* **Added: mistake notebook recording (needs English Finders Core 1.9.0).** The seven answer-checked practice tools (Vocabulary Quiz, Definition Match, Grammar Quiz, Spelling Quiz, Reading Quiz, Sentence Builder, Error Correction) now record a logged-in learner's wrong answers through Core's new `mistakes` service. Each entry holds what the learner actually saw and chose: the question, their answer, the correct answer, the explanation where the tool has one, and the passage for reading. Answering the same question correctly later closes the entry automatically. Items from the hand-written banks are keyed by item id; dictionary words are keyed by the word, so the same word missed in two rounds is one entry.
* To make that possible, each tool's server-side round now also stores a short snapshot of the question. Nothing extra is sent to the browser, and correct answers still never leave the server. Rounds served before an update carry no snapshot, so they are scored as before and simply not recorded. Definition Match still accepts its older stored round shape.
* **Not recorded:** logged-out visitors; the English Level Test (listing its questions with answers afterwards would expose the test); Pronunciation Practice (it has no answer check).
* New `Support\Mistakes` helper, with the same degrade-silently contract as `Support\Activity` (does nothing with Core older than 1.9.0).
* 35 new test assertions (`tests/test_mistakes_integration.php`) covering all seven tools through their real handlers with Core's real `MistakeRepository`.

= 1.12.1 =

* **Fixed: AdSense auto ads no longer inject ad chips into the English Level Test.** Seen live on 2026-09-23 as a "History" chip beside the test's kicker. The test container now carries Google's `google-anno-skip` class, so an ad link can't appear inside a question or option and take a learner off the page mid-test. Ads elsewhere on the page are unaffected.
* **Fixed: Error Correction items with a repeated segment** (e.g. "the ... the" split into identical clickable pieces). 9 items re-segmented by merging neighbouring words into natural phrases. For every one, the sentence text and the segment containing the error are unchanged, which was checked by script against 1.12.0.
* Tests only: `test_grammar_quiz.php` now resets the tool's throttle between simulated sessions. Its 5 failures were the test's own never-expiring transient tripping the 40-rounds-a-minute limit, not a GrammarBank problem; every level has 24 unique items. `test_pronunciation_practice.php` had two over-strict assertions (one matched the word "ajax_answer" in a comment). With these, **the full EFS suite passes with no failures** for the first time since 1.10.0.

= 1.12.0 =

* **Added: the English Level Test (Phase A4), shortcode `[efs_level_test]`.** A free placement test estimating a CEFR level (Pre-A1, A1-C2) for grammar, vocabulary and reading, plus an overall level (the median of the three). It climbs A1 -> C2; at each level a skill needs 2 right before 2 wrong (best of three), and each skill stops at its first failed level, so the test ends when it finds the learner's level: 6 questions minimum, 36 for a perfect run. Rules live in `Assessment\LevelTestEngine` (pure, no WordPress).
* Grammar and reading questions come from GrammarBank and ReadingBank. **Vocabulary comes from a new curated `Assessment\VocabularyBank`** (14 words per level, 84 total), not the dictionary: the dictionary's stored definition is WordNet sense 0, which for roughly 1 in 6 list-verified words is a rare meaning (e.g. "dot" -> "street name for lysergic acid diethylamide"), and would mark down learners who know the word. Every bank word was taken from the live dictionary's list-verified CEFR tags at its filed level; only the definitions are hand-written (everyday meaning, never containing the word or its root). Distractors are other same-level words' definitions. Correct answers never reach the browser; no per-question feedback.
* **No WordPress nonce, on purpose:** the site sends HTML with a 90-day browser cache, which would make a page-embedded nonce stale. A 32-character server session token returned by the start request is the credential instead; a sequence number rejects double-submits and replays.
* Results are stored through Core 1.8.0's `level_results` service. A logged-out taker's result is stored anonymously with a claim token in an HttpOnly cookie (30 days) and attached to their account when they register or log in in the same browser (`user_register` / `wp_login`). The results screen links the matching Tutor LMS CEFR course.
* Not registered in the tool catalog: it spans three skill categories and the catalog allows one per tool. Needs Core 1.8.0; shows an "unavailable" notice with an older Core instead of failing.
* Answers are throttled per test session (60 a minute), not per IP, so a whole class behind one school IP is never cut off mid-test; starting a test is throttled per IP (60 an hour). The first question option is never auto-focused, so nothing looks pre-selected.
* 80 new test assertions (`tests/test_level_test.php`), including a 500-run randomised check of the scoring rules and full end-to-end runs through the real AJAX handlers.

= 1.11.0 =

* **Added: quiz tools now feed English Finders Core's shared activity log (Phase A2 step 3).** Vocabulary Quiz, Grammar Quiz, Reading Quiz, Spelling Quiz, Sentence Builder, and Error Correction each call a new `Support\Activity::record_correct_answer()` helper right after scoring a correct answer; Definition Match calls it once per correct pair in a matched round. New event type `quiz_answer_correct` (added to Core 1.7.1, 1 XP each) — deliberately distinct from Core's `quiz_completed`, since these tools score one question per request with no server-side session concept, so there is no "quiz completed" moment to hook. Pronunciation Practice is unchanged: it has no answer-checking endpoint at all.
* Silently does nothing for a logged-out visitor or when Core is missing/too old (Core 1.7.0+ needed for the `activity` service to exist) — same degrade-gracefully contract every other Core call in this plugin already follows.
* Requires Core 1.7.1 for the event to actually carry XP; against Core 1.7.0 alone the call still succeeds but the event earns 0 XP (an unrecognised event type does, by design), so this is safe to deploy in either order.
* Test coverage: all five affected test files (`test_vocabulary_quiz.php`, `test_error_correction.php`, `test_grammar_quiz.php`, `test_sentence_builder.php`, `test_definition_match.php`) now load Core's real Activity source (not a reimplementation) against a fake `$wpdb`, and assert real XP accrual, per-answer/per-pair granularity, and that a logged-out visitor records nothing. One real regression caught by actually running these tests before packaging: every affected test file fataled immediately (`Call to undefined function get_current_user_id()`) until the shim was added — none of these files had needed that WordPress function before.
* Incidental fix, found while re-running the full suite: `test_sentence_builder.php`'s 200-iteration probabilistic check exceeded `SentenceBuilder::RATE_MAX` (40 calls/minute) partway through, since the test's `set_transient()` shim never expires -- reduced to 20 iterations, still comfortably sufficient to distinguish "reliably solves it" from "gets lucky occasionally". Unrelated to activity logging; this was a pre-existing bug the suite happened to never trigger before.

= 1.10.0 =

* Grammar Quiz now covers all six CEFR levels, A1 to C2, with 24 questions per level — 144 in total, up from 48 at A1–B1 only. Every level's pool is double the twelve-question session length, so a full single-level session never repeats a sentence.
* The A1–B1 cap was originally set because some upper-level grammar carries genuine native-speaker disagreement. That is a property of specific points, not of levels, so the new B2–C2 questions are drawn only from points with one defensible answer: passive voice, reported speech, real and unreal conditionals, relative clauses, gerund/infinitive patterns and linking words, plus harder instances of the original eight points. Every new question was read through for exactly one acceptable option, and several were rewritten or had options removed where two answers were arguable (for example "If I was/were you" and "will" vs "going to" for a prediction). Genuinely contested points — mixed conditionals, subjunctive "that"-clauses, collective nouns with British/American agreement splits — are still left out.
* Nine new topics: pronouns, question forms, future forms, conditionals and wishes, relative clauses, gerunds and infinitives, passive voice, reported speech and linking words. The lower levels also gain eight new questions each (pronouns and question forms at A1, future forms and more pronouns/questions at A2, conditionals, relative clauses and gerunds at B1).
* The level dropdown builds itself from the bank, so B2, C1 and C2 appear without any change to the tool itself. Only the question bank and its test changed; `GrammarQuiz.php`, `grammar.js` and the stylesheet are untouched.

= 1.9.0 =

* Added Pronunciation Practice — the Pronunciation category's first tool, and the last of the six skill categories that has been empty since 1.0.0. A word is shown on screen; the learner taps Speak and says it aloud, and the browser's own speech recognition (`SpeechRecognition` / `webkitSpeechRecognition`) transcribes what it heard. Scoring compares that transcript to the word entirely client-side.
* This is the first tool in the plugin with no answer-checking endpoint at all. Every other tool hides its answer because the client is shown something other than the answer and could otherwise read it out of the page; here the word is the prompt itself, shown as plain text, and the transcript never reaches this server — there is nothing to hide and no round to protect with a token.
* A "Listen" button plays the correct pronunciation via Core's existing text-to-speech pipeline — the same one Spelling Quiz already uses — so a learner can compare what they said against a model pronunciation. Unlike Spelling Quiz, a failed audio lookup does not block the round: the word is still served, just without that button, since audio here is a bonus, not the prompt.
* Gracefully unsupported rather than silently broken on browsers without speech recognition (Safari, Firefox): a clear message explains the tool needs Chrome or Edge, instead of a dead Speak button. Microphone-denied and no-speech-detected are also handled explicitly, with a retry that does not cost the learner a question.
* Shortcode: [efs_pronunciation_practice] — optionally with level="B1".

= 1.8.0 =

* Expanded Error Correction from 24 to 72 items, and from an A1-B1 cap to full A1-C2 coverage (12 items per level). The A1-B1 cap on Grammar Quiz's own bank was never about level itself — it's about specific grammar points (mixed conditionals, the subjunctive, reported-speech nuance) carrying genuine native-speaker disagreement above B1, which a single-correct-answer check can't fairly adjudicate. Error Correction's new B2-C2 material stays inside points that remain just as unambiguous as the A1-B1 set: passive voice, reported speech, real (non-mixed) conditionals, and relative-clause pronoun choice, plus harder instances of the original eight points — the same reasoning `SentenceBank` used to extend past B1 rather than a change of standard.
* Every level's pool (12 items) is now strictly larger than a session's length (10) — the same fix `SentenceBank` needed in 1.7.1 after shipping only 5-8 items per level and letting sessions repeat themselves before naturally ending. `tests/test_error_correction.php` now asserts this directly: ten consecutive draws against one level come back with zero repeats, and a separate check confirms the exhaustion fallback still works correctly once a pool is genuinely drawn dry.
* Four new topics: passive voice, reported speech, conditionals, relative clauses — alongside the original eight (articles, prepositions, present tense, past tense, comparatives, subject-verb agreement, modals, quantifiers), which now also appear at B2 and above with harder sentences.

= 1.7.1 =

* Expanded Sentence Builder from 5 to 12 items per CEFR level (30 → 72 total). Reported cause: a single-level session asks 10 questions (`SentenceBuilder::SESSION_LENGTH`), but each level only had 5 sentences, so every session exhausted its pool partway through and started repeating — the exclusion-list fallback working exactly as designed, just triggered far too early to go unnoticed. Selection itself was already correctly randomised (`array_rand()` per round, a fresh `shuffle()` of both the word-id permutation and the display order every round); the fix is entirely more content, not a randomisation change.
* Every level's pool (12 items) is now strictly larger than a session's length (10), so a full single-level session can no longer need a repeat at all. `tests/test_sentence_builder.php` now asserts this directly: ten consecutive draws against one level come back with zero repeats, and a separate check confirms the old exhaustion-fallback behaviour still works correctly once a pool is genuinely drawn dry (all twelve items).
* New sentences follow each level's existing pattern (plain SVO at A1, adverb placement at A2, question/clause order and light inversion at B1, inversion/cleft/relative clauses at B2, participle clauses and further inversion at C1, advanced inversion and subjunctive conditionals at C2) rather than introducing new patterns, so difficulty stays consistent within each level.

= 1.7.0 =

* Added Error Correction — Grammar Quiz's second tool, and the first case of two tools sharing one category. Where Grammar Quiz shows a sentence with a blank and asks for the correct filler, this shows a complete, fluent-looking sentence with exactly one mistake already in it and asks the learner to spot which part is wrong — recognising an error that isn't flagged for you, rather than choosing a correct form from a shortlist.
* Content comes from a new hand-curated bank (`ErrorBank`), the same shape and scope as `GrammarBank`: the same eight common ESL points (articles, prepositions, present tense, past tense, comparatives, subject-verb agreement, modals, quantifiers), the same A1-B1 cap for the same reason — points with genuine native-speaker disagreement at higher levels aren't attempted here either. 24 items, three per topic.
* Reuses Grammar Quiz's existing sentence/options CSS unchanged (`.efs-quiz__sentence`, `.efs-quiz__options`) — no new stylesheet rules were needed, since the interaction shape (a sentence, up to four options, one correct index, a revealed explanation) is genuinely the same one Grammar Quiz already has.
* Shortcode: [efs_error_correction] — optionally with level="B1".

= 1.6.0 =

* Added Sentence Builder — the Writing category's first tool, filling the fifth of six previously-empty categories (Pronunciation remains). The learner is given a sentence's words in shuffled order and taps them into the correct sequence, rather than selecting from options: every other tool in this plugin asks the learner to recognise an answer, this is the first that asks them to construct one.
* Content comes from a small, hand-curated bank (`SentenceBank`), the same shape as `GrammarBank` and `ReadingBank` — there is no sentence-order dataset in this codebase to query instead. 30 sentences across all six CEFR levels (five per level): plain subject-verb-object order at A1, adverb placement at A2, question and clause order at B1, inversion and cleft sentences from B2 up. Reading Quiz already established that a word-order or comprehension check can stay unambiguous at any level, unlike Grammar Quiz's contested-rule cap at B1, and the same reasoning applies here.
* Correctness is exact by construction: each word carries the array index it holds in the bank's original ordering as its id, so a submission is correct exactly when the submitted ids come back as 0, 1, 2, …, n-1 — there is no separate answer key to compare against.
* Shortcode: [efs_sentence_builder] — optionally with level="B1".

= 1.5.9 =

* Fixed the actual cause of the mismatched Spelling Quiz row, after three releases (1.5.5-1.5.8) spent resizing the input itself without touching the real problem. The Check button renders with the shared `.efs-quiz__next` class (see spelling.js), which everywhere else in this file is a standalone "next question" button meant to sit *below* other content — hence its own `margin-top: 0.9rem`. Placed inline next to the input in this one form, that inherited margin pushed the button 15px down the row, offsetting its top edge from the input's by exactly that amount. Measured directly with `getBoundingClientRect()` on both elements before writing any CSS, not assumed from a screenshot.
* Fixed by cancelling that margin-top only inside this form's row layout (`.efs-spelling__form .efs-quiz__next { margin-top: 0; }`), with `align-items: center` added so any small residual size difference no longer shows as a visible edge offset. The mobile breakpoint restores the same margin-top, since stacked there (input above, button below) it's exactly what supplies the gap between them — confirmed unchanged by re-checking mobile before shipping, since the user had explicitly confirmed that layout already looked right and it was not to be touched.

= 1.5.8 =

* Fixed a regression 1.5.7 introduced on mobile while fixing desktop height: raising the base `.efs-spelling__input` rule's specificity to `.efs-quiz .efs-spelling__input` (0,2,0), to beat Astra's competing rule, meant the existing mobile-breakpoint override — still written as the bare `.efs-spelling__input` (0,1,0) — stopped winning too, even though its `@container` condition was true. A container query changes *when* a rule applies, not its specificity, so the override needed the same prefix. Caught by re-testing mobile immediately after deploying the 1.5.7 desktop fix, rather than assuming a change scoped to "desktop" couldn't affect it.

= 1.5.7 =

* Fixed, for real this time: Spelling Quiz's answer box was still visibly taller than the Check button next to it. The user correctly called out that the previous two releases (1.5.5, 1.5.6) had been chasing the wrong dimension — width, not height — and asked for the actual root cause before another guess. Found it by walking every stylesheet rule that matched the live input element: Astra's theme CSS sets `input[type="text"] { padding: 0.75em; height: auto; }`, and that selector's specificity (0,1,1) beat this plugin's plain `.efs-spelling__input` class selector (0,1,0) outright — the box's own intended `0.7rem 1rem` padding was never actually being applied. The same theme-vs-plugin gap this file's own `.efs-quiz .efs-quiz__word` rule already had to solve once before, just not yet applied here.
* Fixing the padding alone was still not enough: Astra's competing `height: auto`, combined with how a browser sizes a text `<input>`'s content box (by its own intrinsic line metrics, not by this stylesheet's `line-height` the way a block element would be), meant even a `min-height: 48px` floor did nothing — the box was already taller than that floor. Replaced it with an explicit `height: 48px`, and raised the selector to `.efs-quiz .efs-spelling__input` (specificity (0,2,0)) so it wins outright, with no `!important` needed. Verified directly against the live page with the un-prefixed rule still active: without the fix, height difference against the Check button was 15.3px; with it, 3.3px.

= 1.5.6 =

* Fixed: Spelling Quiz's answer box still looked too wide on desktop after 1.5.5. Root cause of the persisting issue: 1.5.5 matched Best Word Challenge's input-to-card *ratio* (~71%), but that ratio isn't what makes Best Word Challenge's input look right — it looks right because it sits below a wide letter-tile rack it can visually align with, something this field has no equivalent of. Matching an unrelated ratio still produced a box far bigger than a spelling answer needs. Re-sized instead to what the field actually has to hold: the longest word this tool can serve is 15 letters (checked against the live dictionary query the tool itself uses), which fits comfortably in 16rem. Verified with a 15-character test string typed into the field before shipping: no overflow, comfortable margin.

= 1.5.5 =

* Fixed: Spelling Quiz's answer box looked noticeably wider than the site's other similarly-shaped input, Word Games Pro's Best Word Challenge. Measured both live at the same viewport width to confirm before touching CSS: Spelling Quiz's input filled ~80% of its card, against Best Word Challenge's ~71% — the difference is that Best Word Challenge pairs its input with two buttons (Submit + Clear), which together claim more of the row, while Spelling Quiz has only one (Check), so its input's flex-grow had nothing else to yield space to. Capped the input at `max-width: 30rem` on desktop, reset back to full width on the existing mobile breakpoint so the previous mobile fix is untouched.

= 1.5.4 =

* Fixed: Spelling Quiz's answer box became several hundred pixels tall on mobile. `.efs-spelling__input`'s `flex: 1 1 14rem` sizes it along the form's main axis — a width when the form lays out as a row, but the mobile breakpoint switches the form to `flex-direction: column`, which flips that same axis to vertical. The 14rem became a height instead of a width, and flex-grow stretched it further. The mobile rule now cancels the input's flex sizing so `align-items: stretch` handles width instead.
* Added 24 passages to Reading Quiz (12 → 36), extending coverage from three levels (A1–B1) to all six (through C2). Reading comprehension doesn't share grammar's reason for capping at B1 — a passage's vocabulary and sentence complexity can scale to C2 while the question itself stays a plain, directly-supported detail or inference, so extending coverage doesn't risk the ambiguous-answer problem grammar has at higher levels. New topics: family, transport, shopping, technology, education, culture, economics, science, linguistics, history.

= 1.5.3 =

* Added Reading Quiz — the Reading category's first tool, fills the fifth of six previously-empty categories (Writing and Pronunciation remain). A short passage and one comprehension question about it; the answer must be directly supported by a sentence in the passage. Like Grammar Quiz, content comes from a small, hand-curated bank (`ReadingBank`) — there is no reading-comprehension dataset anywhere in this codebase to query instead, the same reason Grammar Quiz has its own bank.
* 12 passages across A1, A2, and B1 (four each), the same level range Grammar Quiz covers and for the same reason: a passage needing genuine ambiguity or subtext to be level-appropriate stops being a fair, unambiguous comprehension check.
* Every question is answerable directly from the passage text — no interpretation, no outside knowledge required — and the explanation shown after answering quotes the specific supporting sentence.

= 1.5.2 =

* Added Spelling Quiz — the Spelling category's first tool, fills the fourth of six previously-empty categories. The learner hears a word and types what they heard; no text is shown before answering. Words are drawn from Core's dictionary by CEFR level, same as Vocabulary Quiz; audio is generated through Core's existing text-to-speech pipeline (`Api::service('audio')`), the same one the Word Details page's pronunciation button already uses, so no new audio infrastructure was built.
* The correct spelling is never sent to the browser before scoring — the round carries only an audio URL, matching the server-held-answer pattern the other tools already use. If the randomly-picked word's audio isn't cached and fails to generate, the round quietly retries with another candidate rather than surfacing the failure to the learner.
* Uses only generated speech (not recorded-audio lookup), since recorded-audio candidates require a live external dictionary API call this tool has no reason to pay for.

= 1.5.1 =

* Fixed: Grammar Quiz's sentence blank showed two lines instead of one. The blank was rendered as the literal text `______` styled with its own `border-bottom` underline — the underscore glyphs and the CSS underline stacked into two visible lines. The blank now renders as empty, fixed-width space with only the CSS underline, matching every other quiz's answer-blank treatment.

= 1.5.0 =

* Added a Dashboard and Settings screen under Study in wp-admin — the admin surface this plugin has never had. Dashboard: how many tools are registered and enabled, which of the six skill categories are active, and whether Core is connected. Settings: enable or disable each tool independently, and choose whether uninstalling the plugin also deletes its data.
* Every tool now carries a stable enable/disable state (`efs_settings['disabled_tools']`) and `uninstall.php`'s existing `delete_data_on_uninstall` check finally has a UI to set it — both settings keys have shipped since 1.0.0 with nothing able to change them until now.
* A disabled tool's shortcode and AJAX handlers are never registered at all, not merely hidden — matching how Word Games Pro's own module toggles behave. Its category drops out of navigation too, if nothing else in that category is still enabled.
* `ToolInterface` gains `title()` and `shortcode_tag()`, so the admin's tool list can show a real name and shortcode per tool instead of a second, drift-prone copy of strings each tool already had inline. All three shipped tools implement both.
* Gated on the built-in `manage_options` capability — no new capability-granting machinery for a plugin that has never needed one.

= 1.4.0 =

* Added Grammar Quiz, the first Grammar-category tool and the third overall. A sentence with a blank and four options — pick the word or phrase that completes it correctly.
* Shortcode: [efs_grammar_quiz] — optionally with level="B1".
* Unlike the vocabulary tools, questions are not drawn from the dictionary — there is no sentence-grammar dataset in this codebase — so this ships with a small, hand-curated bank of 48 items across eight common points (articles, prepositions, present tense, past tense, comparatives, subject-verb agreement, modals, quantifiers) at A1–B1. Extending coverage to B2 and beyond means adding items to the bank; nothing else in the tool changes shape.
* An explanation is revealed alongside the answer — the vocabulary tools don't have one, since a grammar rule benefits from a reason in a way a dictionary definition already is one.
* As with the other tools, the correct answer never reaches the browser and a round can only be answered once. A session tracks which sentences it has already shown so a 12-question round does not repeat itself against a bank this size.
* Grammar becomes a populated category, so it now appears in navigation for the first time.

= 1.3.0 =

* Added Definition Match, the second vocabulary tool. Four words, four shuffled meanings, pair them up. Where the quiz asks whether you recognise one meaning, this asks whether you can tell four apart — a harder skill from the same data, still with no question bank to write.
* Shortcode: [efs_definition_match] — optionally with level="B1".
* Sessions run to five rounds and report a total. Partial credit is scored, so getting two of four pairs right counts as two.
* As with the quiz, the correct pairing never reaches the browser and a round can only be submitted once.
* Breadcrumb handling moved into a shared helper now that two tools need it, rather than being copied.

= 1.2.2 =

* Removed a large gap under the quiz word. It renders as a paragraph, and the theme's paragraph margin is set in em — at the word's font size that came out as 66 pixels of empty space. The plugin's own rule was losing the specificity contest, so it now sits above the theme's.
* Reduced the word size. It was scaling to 44 pixels on a desktop card, which overwhelmed the options beneath it.
* Applied the same specificity fix to the other text in the card, so theme margins cannot reappear elsewhere.

= 1.2.1 =

* Removed the decorative corner circle from the card header. It works on the word detail page, which is a wide sparse banner, but crowded the heading on a narrow card.
* Spacing now scales with the card rather than being fixed, so a phone no longer spends a large share of its width on padding. Narrow cards also get tighter option spacing and a full-width action button.
* Sizing is driven by container queries, so it adapts whether the quiz sits full width on a phone or in a narrow column on a desktop page. A viewport-based fallback covers browsers without container query support.

= 1.2.0 =

* Sessions now run to twenty questions and end with a result and a "Start a new session" button, instead of continuing indefinitely. Changing the level starts a fresh session, since a score mixing A1 and C1 answers would not mean much.
* The card header now uses the same gradient and soft corner shape as the word detail page, so the two read as one product.
* The breadcrumb now shows Home / Tools / page. The intended Vocabulary Tools step is omitted for now because that page does not exist yet; both the Tools URL and the whole trail are filterable (efs_tools_url, efs_breadcrumb_trail) so it can be inserted without a code change.

= 1.1.1 =

* Redesigned the quiz card: gradient header echoing the word detail page, kicker label, and part-of-speech and level shown as tags rather than a plain line.
* Fixed unreadable hover text. The answer options are buttons, so the theme's own button hover was repainting the label white against a pale background. Every state now declares its own colour, including the answered and disabled states.
* Added a breadcrumb with BreadcrumbList structured data, built from the page's real ancestry. Use breadcrumb="0" to hide it, or schema="0" if your SEO plugin already outputs a BreadcrumbList for the page — two on one URL is worse than none.

= 1.1.0 =

* Added the Vocabulary Quiz, the first practice tool. Shows a word and four definitions; pick the right one. Words come from the shared dictionary and can be filtered by CEFR level, so no question bank had to be written.
* Shortcode: [efs_vocabulary_quiz] — optionally [efs_vocabulary_quiz level="B1"].
* The correct answer is never sent to the browser: each round is stored server-side behind a token and scored on the server, and a round can only be answered once.
* Requires English Finders Core 1.4.1 or later.

= 1.0.0 =

* Initial scaffold: plugin bootstrap, PSR-4 autoloader, tool catalog with six skill categories and cross-cutting attributes, tool contract, own migration ledger and runner, activation and deactivation handling with multisite support, and opt-in uninstall.
* No tools yet. This release establishes the structure they plug into.
* Guards against an incompatible Core with an admin notice rather than failing at the first service call.
