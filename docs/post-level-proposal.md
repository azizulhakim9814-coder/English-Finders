# Proposed CEFR levels for unlevelled blog posts (for owner review)

Prepared 2026-09-28 for Recommendation 5A. **Nothing has been assigned yet.** Once approved, a post gets a level by being added to one of the six level categories (`a1-english-beginner` … `c2-english-proficiency`). Study 1.17.0 then shows the "Written for B1 Intermediate learners" row with links to `/learn/b1/` and the B1 course.

88 published posts have no level category. Most should stay that way: a level label only helps a post that teaches language at a particular level.

## Propose a level (5 posts)

| Post | ID | Proposed | Why |
|---|---|---|---|
| 50 English Grammar Quiz With Answers | 9451 | **B1** | Mixed grammar that goes up to reported speech and modal verbs |
| 10 Common Grammar Mistakes in English | 8572 | **B1** | Everyday grammar errors that intermediate learners make |
| The Importance of Punctuation in the English Language | 9619 | **B1** | Punctuation rules with examples, written in plain but not beginner language |
| 20 Must-Know Phrasal Verbs for IELTS | 8684 | **B2** | IELTS audience; idiomatic phrasal verbs |
| Essay on Climate Change For ESL Students | 9371 | **B2** | A 500+ word model essay with academic vocabulary |

## Optional (functional language; your call)

| Post | ID | Proposed | Note |
|---|---|---|---|
| 10 Best Ways to Start a Conversation in English | 17 | A2 | Teaches opening phrases, though it is mostly advice |
| 10 Proven Tips to Introduce Yourself in a Job Interview in English | 5250 | B1 | Mostly interview advice, with some model phrases |

## Leave unlevelled (81 posts)

- **Five-letter word lists** (33781, 33482, 32900): the words span every level. Checked against the dictionary's CEFR tags, for example 33781 has A1 17 · A2 7 · B1 8 · B2 14 · C1 10 · C2 3. They serve word-game players, not one level.
  - **Content bug spotted:** 32900 lists "abley" ("She completed the task abley"). That is a misspelling of "ably" and is not a five-letter word.
- **Study advice and skill guides** (how to improve reading, writing, speaking or listening; vocabulary tips; fluency; pronunciation; accents; learning online): useful at any level. They keep the practice box's "Not sure of your level? Take the free level test" line, which is the right next step for a reader whose level we don't know. The rebuilt skill hubs (`/reading/`, `/writing/`, `/speaking/`, `/listening/`) now list these posts as cards.
- **IELTS strategy** (tips for each IELTS paper, IELTS quiz 8932): exam technique, not level-graded language.
- **Reviews, gear and off-topic** (WordHero, Preply, Babbel, Grammarly, teacher supplies, translation apps, plagiarism, writing tools, home-office nook, Scrabble, ChatGPT guides, becoming a teacher): not learner content.

To apply the approved rows: `wp_set_post_categories( $id, array( <level term id> ), true )` (append), then purge the post by ID.
