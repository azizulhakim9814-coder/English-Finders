# A1 course deepening (Rec 5C pilot, 2026-09-28)

All 84 lessons of the Tutor LMS course **English Finders A1 — Beginner** (course 34908) were rewritten from these sources and written to englishfinders.com through `execute-php`.

## Files

- `m0.py` … `m11.py`, `mF.py`: lesson content as structured data, one file per module (Module 0 … 11, then Final).
- `build.py`: turns the data into lesson HTML (`out/<id>.html`) and prints each lesson's text length and SHA-256.
  - Four lesson kinds: `lesson`, `cando`, `worksheet`, `raw`.
  - It also checks every Connect link against the list of verified live pages.
- `deploy.py <id>…`: prints the PHP for one request.
  - It checks each body's SHA-256 before writing anything.
  - It skips any lesson that changed on the live site since the backup.
  - It re-hashes each lesson after writing and purges it by ID.
- `a1_desc.php`: regenerated the 72 non-worksheet Rank Math descriptions, from the Hook paragraph (or "Your Task" for Can-Do lessons). Worksheet descriptions don't depend on the lesson text and were left as they were.

## The lesson template

| Kind | Sections |
|---|---|
| Core lesson | Hook → Notice → Rule → Watch Out (✗ → ✓) → Practice (8 items, answers in a collapsible "Show the answers") → Your Turn → Connect → CEFR A1 tag |
| Can-Do Task | Your Task → Before You Start → Useful Language → Model Answer → Check Your Work → Go Further → Connect |
| Worksheet | Keeps the original parts and the printable image; adds extra online parts; answer key at the bottom |

## Rules for future edits

- Keep worksheet Parts A–C as they are where an uploaded image exists: the printable sheet matches them.
- Connect links name the tool the way learners see it ("our Grammar Quiz"), never the plugin.

## Result

- Visible text per lesson: every lesson was under 1,500 characters before (typically 600–1,400). Now the median is 2,121 and the range 1,479–2,880; the two shortest are worksheets.
- Every Connect URL returned 200.

## Backups

On the server, in `ef-backups/`:
- `a1-lessons-20260928-093847.json`: the original post content of all 84 lessons.
- `a1-meta-desc-20260928-102959.json`: the previous descriptions.

To roll back one lesson:

```php
wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $bk[ $id ]['content'] ) ) )
```

## Errors in the old content, fixed on the way

- Worksheet 6's key said a sandwich plus a tea was $8.00; it is $7.50.
- Worksheet 8's key gave "blue new shoes"; the correct order is "new blue shoes".
- The Final module promised a recorded listening clip that doesn't exist, and a certificate the course doesn't clearly issue.
- A few Connect buttons used internal tool names ("DefinitionMatch", "VocabularyQuiz"), or pointed to tools that don't practise the skill (Pattern Hunt for question order, Word Finder for hobbies).
- Answers were printed inline next to the questions; they now sit behind "Show the answers".
