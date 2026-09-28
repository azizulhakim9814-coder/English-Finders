# Learn hubs builder (`/learn/`, `/learn/a1/` … `/learn/c2/`, and the skill hubs)

These scripts build the Learn hub pages on englishfinders.com as Elementor pages, following the site's hub recipe (hero + CSS-grid card widget, 12 cards per page). Each is sent as the `code` of the Novamira `novamira/execute-php` ability.

| Page | ID | Contents |
|---|---|---|
| `/learn/` | 35893 | Level Test + six level cards; "Learn by skill" grid (Grammar, Vocabulary, Reading, Writing, Speaking, Listening hubs) |
| `/learn/a1/` … `/learn/c2/` | 35896, 35899, 35902, 35905, 35908, 35911 | The level's course, words, articles, Level Test and the 8 practice tools; then Grammar Quiz + Vocabulary Quiz embedded, preset to that level |
| `/reading/`, `/writing/`, `/speaking/`, `/listening/` | 34387, 34386, 34388, 34389 | Three card grids (practice tools + Level Test + Learn by Level; the skill's lessons picked from the A1–C2 courses; the skill's articles), then one tool embedded (Reading Quiz / Sentence Builder / Pronunciation Practice / Spelling Quiz) |

## Running it

1. Build or refresh `/learn/`: send `lib.php` + `levels.php` + `learn_page.php` concatenated, as one request.
2. Build or refresh the level pages: send `lib.php` + `levels.php` + `level_pages.php` concatenated, as one request.
   - To rebuild only some levels, prepend `$efl_only = array( 'B1' );`.
3. Build or refresh the skill hubs: send `lib.php` + `levels.php` + `skill_pages.php` concatenated, as one request.
   - To rebuild only some, prepend `$efl_only = array( 'reading' );`. Prepend `$efl_dry = true;` to list the lessons and articles without saving anything.
   - The lesson lists are curated IDs in `$efl_skills` (the course has few lessons per skill; the Can-Do tasks that are really writing tasks were left off the Speaking hub). The articles are every post in the skill's category (Writing also includes `essays`), newest edit first.
   - The run also rewrites the hover CSS block to cover every page ID already in it plus these four.

`efl_save_page()` updates a page that already exists (matched by path) rather than creating a duplicate, so re-running is safe. Each run:
- recomputes the lesson counts from Tutor, the word counts from `wp_wuc_words.cefr_level` and the article counts from the level categories;
- regenerates Elementor element IDs;
- rewrites the page's Rank Math description and focus keyword.

**Re-run after adding lessons, words or level articles**: the numbers on the cards are written in at build time.

## Rules these scripts already follow
- `<style>` / `<script>` tags are assembled on the server (`'<' . 'script>'`) so Wordfence doesn't block the request.
- Pages are purged by post ID only. **Never** purge-all LiteSpeed (see `CLAUDE.md`). If the menu changes, also purge all published pages and courses by ID.
- The hover-lift CSS lives in Additional CSS between the `EF-LEARN-HUBS-START` and `EF-LEARN-HUBS-END` markers, scoped to the 11 page IDs above. `skill_pages.php` shows how to add IDs to it.
