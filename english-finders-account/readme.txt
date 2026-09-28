=== English Finders Account ===
Contributors: englishfinders
Requires at least: 6.6
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.19.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Registration, login, profile and privacy for English Finders. Replaces the broken /my-account/ page.

== Description ==

English Finders Account is Phase A1 of the "My Account" build described in the english-learning-hub project skill (see a1-account-foundation.md and my-account-design.md). It is deliberately narrow: registration, login, a small profile (display name, native language, daily goal), four privacy/notification toggles, and self-service data export/delete via WordPress's own core Privacy Tools.

= Why this plugin exists =

`/my-account/` on englishfinders.com currently renders the `[woocommerce_my_account]` shortcode while WooCommerce is inactive, so the page is broken. This plugin intercepts that page (by slug, via `template_redirect`) and renders a working account page instead — no content edit to the live page is required to deploy it.

= What this is not =

My Level, the mistake notebook, the daily goal, the opt-in weekly leaderboard and course certificates have all shipped since Phase A1 (see the changelog). The teacher role (Phase A7) is not included here. See the phase table in `my-account-design.md`.

= Requirements =

English Finders Core 1.7.4 or later, declared via `Requires Plugins` — a hard dependency, since login/registration cannot function without it. Word Games Pro 2.12.12 or later is a soft dependency: when it's active and new enough, the My Library section (0.5.0) shows saved words and recent searches through its `Support\Api` facade; when it isn't, that section is simply absent, the same degrade-gracefully pattern used for Core's own optional services. This started as a phase-A1 plugin with no dependency on Core; Phase A5 (billing UI) first added a real one — EntitlementRepository, PaddlePortalClient, TransactionLog — Phase A3's Progress section (0.4.0) raised the Core minimum to 1.7.0 for the `activity` service, and 0.6.0's badge progress bars raised it again to 1.7.4 for `BadgeCatalog::thresholds()`.

== Changelog ==

= 0.19.1 =
* My Account's daily-goal hint now matches English Finders Core 1.14.0's XP rules: "1 XP per correct answer in practice, quizzes and games, 5 per solved puzzle, 20 per lesson." (was "1 XP per correct practice answer, 10 per game, 20 per lesson."). Text only; with Core 1.14.0 the Progress section's streak also reads 0 once it has lapsed, with no change needed here.

= 0.19.0 =
* "Go Pro" links, behind a switch (Settings -> English Finders Pro, off by default, only while Pro is on sale): a "★ Go Pro" button in the header next to My account, and a one-line "Tired of ads? Go Pro…" note after articles, both for signed-in free members only (never for visitors, whose pages are cached). Pro members get a "PRO" badge on their My account button. The Refund Policy page is ad-free (its old cached copy is purged once). Filters: efa_pro_promo_enabled, efa_pro_note_enabled, efa_pricing_page_url.
* Crawl load: My Account, /login/ and /sign-up/ send noindex, nofollow (X-Robots-Tag header + Rank Math / core robots meta), and every login / sign-up link that carries a return address (header, Weekly Top 5, pricing page, Tutor lesson note and login page) is rel="nofollow". The access log showed crawlers rendering thousands of these uncacheable ?efa_return= pages a day; robots.txt now blocks them too.

= 0.18.2 =
* The pricing page is never cached (LiteSpeed, CDN or browser), so prices and "on sale" always show live; its old cached copy is purged once after the update. Pricing footnote links the new Refund Policy (14 days) next to Terms and Privacy. PricingPage::is_pricing_page() shared by AdFree and CachePolicy. Filter: efa_pricing_page_urls.

= 0.18.1 =
* No ads on the pricing page, /login/, /sign-up/ or My Account, for anyone (AdSense auto ads had put ad links inside the pricing cards). Same switches as Pro's no-ads (Site Kit AdSense tag, WPCode header box, Ad Inserter); decided by the page, so cached guest copies are correct. Filter: efa_ad_free_page.

= 0.18.0 =
* Pro (behind a switch): no ads for Pro members (Site Kit AdSense tag, WPCode header box and Ad Inserter blocks switched off per member; admins can preview with ?efa_pro_preview=1), [efa_pricing] Free vs Pro page ($2.99/month or $24.99/year), Settings -> English Finders Pro (environment, client-side token, monthly/yearly price IDs, checklist, webhook address). Pro is on sale only once the token, a price and the webhook secret are set. My Account Membership shows yearly and monthly buttons; checkout fills in the email and lands on My Account with a welcome note.

= 0.17.0 =
* "Keep your progress" bar for visitors who are not signed in: the first time in a visit that a practice tool or game reports progress (the shared ef:progress event from English Finders Study 1.13.1 / Word Games Pro 2.12.13), a small bar offers a free account and returns them to the page. Once per visit; dismissing it hides it for 3 days. Never shown to members or on the account pages.
* Old prompts now go to /sign-up/ and /login/ instead of My Account: the level test's "Create a free account", the Tutor lesson "Reading as a guest" note (two links), and the Tutor "Log in to continue" page (two buttons), all returning to where the learner was.

= 0.16.1 =
* Hidden Tutor profiles now show the 404 page straight away, so the site's "404 -> similar post" redirect plugin can no longer turn them into a 301 (it sent them to the leftover PMPro /membership-account/your-profile/ page). Other missing pages are unaffected.

= 0.16.0 =
* One way in: Tutor's student registration and new-instructor sign-up, and WordPress's own registration, now lead to /sign-up/ (Turnstile, rate limit, full name); their form handlers are refused, not just the pages. Signed-in members can still apply to teach from their Tutor dashboard. "Register" links no longer reveal the hidden login address.
* Tutor public profiles only for approved instructors and staff; every other profile is a real 404 (including ?view=instructor). Outside links on the remaining profiles get rel="nofollow ugc".
* New Users -> Suspicious sign-ups screen: accounts with hard evidence of spam (profile links or a pending instructor application, and no sign of real use), handed to WordPress's own Delete Users confirmation. Nothing is deleted automatically.

= 0.15.2 =
* My Account leaderboard: ranks 1-3 are gold, platinum and bronze coins too, matching the sidebar card (keyed on each row's rank, so ties share a coin).

= 0.15.1 =
* [efa_weekly_top]: ranks 1-3 are now gold, platinum and bronze coins (metallic sheen, number stamped in a dark shade of each metal; contrast 5.6:1 or better where the digits sit). Tied ranks share a metal.

= 0.15.0 =
* Leaderboard: members' own profile photos (uploaded, or from Google sign-in) in the circles, initials when there is none. The Join text now says exactly what is shown and to whom.
* New [efa_weekly_top] shortcode (limit="1-10", title="..."): a Weekly Top 5 card for sidebars. Signed-in members see names and photos; visitors who aren't signed in see anonymous ranks and XP with a "Sign up free" button, refreshed on load from a small static file (uploads/efa-public/weekly-top.json, ranks and XP only) so cached pages never show stale numbers and no PHP runs for it.

= 0.14.3 =
* Sign-up: "Confirm password" field (with its own Show button). A live line under it says whether the two match, and the form will not submit while they differ; the server refuses a mismatch too (password_mismatch), keeping the name, email and role but never the passwords.
* My Account password change: the same live match check on "Confirm new password".

= 0.14.2 =
* Header links: "My account" is now an outlined button (same height and shape as "Sign up") and is vertically centred with the menu; the row was inline and sat on the text baseline, about 3px high.
* Header links: "Log in" uses the header menu's own font, size and weight, read from Astra's primary-menu settings (filter efa_account_links_menu_typography for other themes).

= 0.14.1 =
* Fix: header links' return address on Word Games Pro's virtual /dictionary/ pages pointed to an unrelated post; it is now built from the address actually requested.
* Fix: header link styles are marked data-no-optimize so LiteSpeed keeps them inline instead of dropping them from pre-built "unused CSS" files (links were unstyled on the homepage).

= 0.14.0 =

* **Added: standalone login and sign-up pages.**
  * `[efa_login]` goes on a page at /login/ and `[efa_signup]` (alias `[efa_register]`) on a page at /sign-up/, so the top menu can link to each separately.
  * Each page shows one focused form with a heading and a link to the other page ("New to English Finders? Create a free account →" / "Already have an account? Log in →").
  * The form sits beside a brand panel whose numbers are counted from the site: courses, lessons and dictionary words. On phones the form comes first. Use `panel="no"` for the form alone.
  * The forms are the same ones as on My Account (moved to `templates/public/partials/`), so Turnstile, Google, full name, learner/teacher, photo and "back to where you were" all work there too.
* **Mistakes come back to the same page** (`efa_origin`): a wrong password on /login/ returns to /login/, and a sign-up mistake returns to /sign-up/ with the typed values kept, including through Google sign-in.
* Logged-in visitors skip these pages and go to where they were heading or to My Account. The exception is the Elementor editor/preview, where the page stays editable and shows a short notice.
* These pages are never cached (their forms carry nonces). Page detection covers both classic pages and Elementor Shortcode widgets. If the pages use other slugs, set them with the filters `efa_login_page_url` / `efa_signup_page_url`. Until the pages exist, all links fall back to My Account.
* **Added: `[efa_account_links]`** for the site header (Astra's HTML header element). Guests see "Log in" plus a "Sign up" button, both returning to the current page afterwards. Members see their photo plus "My account", so "Sign up" never shows to someone logged in. Options: `show="both|login|signup"`, `login_text`, `signup_text`, `account_text`.
* **Added: Show / Hide on password fields** (sign-up, login and the Password section), so learners can check what they typed instead of typing it twice. The buttons only appear once the script runs.
* "Remember me" and "Forgot your password?" now sit on one row.
* 50 new test assertions (`tests/test_auth_pages.php`).

= 0.13.2 =

* **Fixed: small photos could not be uploaded.** The photo was made square with the image editor's `resize( 256, 256, crop )`, which never enlarges. For any photo whose short side is under 256px it failed with "Could not calculate resized image dimensions" (a 120×150 passport photo, or 200×200), and the learner was wrongly told the photo "must be a JPG or PNG". Some larger photos (e.g. 300×200) came out not square. The photo is now centre-cropped to a square explicitly: small photos keep their own size (120×150 → 120×120), larger ones are scaled to 256×256, and nothing is ever enlarged.
* Clearer photo messages: "too small" (under 64px on a side), and "could not be processed" instead of the misleading "must be JPG or PNG".
* **Photo size limit back to 2 MB** (0.13.1 raised it to 5 MB, which strained the server). The browser already shrinks photos to around 100 KB. It now also refuses a photo that would still be over 2 MB, or that is under 64px, before uploading anything, using the same limits as the server.

= 0.13.1 =

* **WordPress's toolbar is hidden from learners.** It was shown on the front end to every logged-in user (WordPress's default, on for all accounts), and it led learners into the WordPress dashboard and profile screens. It is now hidden on My Account for everyone, and everywhere for learners. Staff (anyone who can edit posts, and Tutor instructors) keep it on other pages. The filter `efa_show_admin_bar` adjusts this. (`Support\Toolbar`)
* **Profile photo: instant preview and fast upload.** Choosing a photo now shows it straight away in the avatar circle, on sign-up and in Profile. The browser then crops it to a square and shrinks it to at most 512×512 (JPG stays JPG, PNG stays PNG) before upload. A phone photo of several MB becomes about 50–150 KB, so saving is quick. Before this, the full-size photo was uploaded, which was slow on mobile data and refused over 2 MB. Anything other than JPG or PNG is caught immediately with a message, the Save button shows "Saving…", and the server still checks and re-encodes whatever arrives. (`assets/js/avatar-picker.js`, kept out of LiteSpeed's JS minifier)
* The server's photo limit is now 5 MB (was 2 MB). It only matters when the browser can't shrink the photo first.
* Fixed: the home cards' headings were still title-cased by the theme ("Review Your Mistakes").

= 0.13.0 =

* **Added: "Continue with Google"** at the top of both the sign-up and login cards. It uses the server-side OAuth flow with PKCE, and a `state` value tied to the browser by an HttpOnly cookie. The code is exchanged at Google's token endpoint, and the ID token's issuer, audience, expiry and verified email are checked.
  * An existing account with the same verified email is linked. Otherwise a new account is created with the full name and photo from Google.
  * Staff accounts (anyone who can edit others' posts) are refused and must use their password.
  * The button appears only once `EFA_GOOGLE_CLIENT_ID` and `EFA_GOOGLE_CLIENT_SECRET` are set in wp-config.php. Redirect URI: `/wp-admin/admin-post.php?action=efa_google_callback`.
* **Added: learner / teacher choice** at the top of sign-up. It is passed through Google sign-in too, and is editable in Profile. Teachers get a "Teacher" badge. It is only a label for now: no WordPress or Tutor role depends on it.
* **Full name is now required** (2–80 characters, must contain a letter, no email addresses). It is saved as the display name and also split into first and last name, which certificates use.
* **Added: profile photo**, optional at sign-up and changeable or removable in Profile. JPG or PNG up to 2 MB, checked by content (not by file name), re-encoded as a 256×256 square, which drops photo metadata. It is shown site-wide through `get_avatar()`. A Google photo is used when nothing has been uploaded. Privacy erasure deletes the file.
* **Added: Terms line** under Create account: "By signing up, you agree to our Terms of Service and Privacy Policy.", with links to /terms-of-use/ and /privacy-policy/.
* **Added: Password section.** Current password, new password and confirmation; WordPress emails the learner, and other devices are signed out. Accounts created with Google can set a first password without a current one.
* **Fixed: the sign-up page overflowed** sideways between about 640 and 900px. The theme also capitalised card headings, and labels had doubled gaps below them.
* **Fixed: "Your data" buttons** stacked on phones. They now sit side by side at every width, and Delete has a quieter red outline style. Log out is now a button.
* **Fixed: My Library empty states** linked to Games. Saved words now link to the word unscrambler (where the heart that saves a word is), and recent searches link to Word Tools. Saved words link to their dictionary pages, and dates are readable. The stale "mistake notebook is coming" line is replaced with a link to the notebook.
* A failed sign-up keeps the name, email and learner/teacher choice (never the password). They are held for 10 minutes on the server, so the email address is never put in the URL.
* 92 new test assertions (`tests/test_account_settings.php`).

= 0.12.0 =

* **Fixed: learners could not log in from course pages.** Tutor LMS shows its own sign-in form to guests on quiz pages, on /dashboard/ and in the pop-up behind course "Sign in"/enrol buttons. Wordfence requires a CAPTCHA on this site, and Tutor's form never sends one, so correct passwords were rejected and a verification email was sent instead. Tutor's form is now replaced by a "Log in or sign up" button to the My Account login (Turnstile-protected, Wordfence handled). No Tutor settings were changed. The filter `efa_replace_tutor_login` switches this off.
* **Fixed: the My Account login page was cached.** LiteSpeed served guests one shared copy of /my-account/ for up to 7 days, and the site's .htaccess told browsers to keep it for 90 days. Its login and registration nonces expire within 24 hours, so logging in from a cached copy could fail. /my-account/ now sends no-cache headers and tells LiteSpeed not to cache it (`Support\CachePolicy`). LiteSpeed doesn't purge on plugin updates here, so the first request after an update purges, once, the pages this plugin's output changed on: My Account, Tutor's dashboard, and the course and quiz pages. It purges them by id, never the whole cache.
* **Return to where you were:** logging in or signing up from a lesson, quiz or course page now returns the learner to that page (`efa_return`, same-site URLs only). This also applies to the guest note on lessons, and a learner who is already logged in is sent straight back.
* **Added: "Continue where you left off"** at the top of My Account. It shows the course in progress, % done, items done out of total, and the next lesson or quiz, with a Continue button.
* **Added: one suggested next step**, first match wins: take the level test, start the course for your level, review open mistakes, reach today's goal, then play a game.
* **My Level course list** now shows "12 of 97 done · Next: <lesson>" for courses in progress, and "Completed" with a link to the certificate for finished courses.
* The course progress numbers are Tutor's own (lessons completed plus quizzes submitted, out of all course items), so they match Tutor's progress bar. The course logic moved from LevelController into `Learning\CourseProgress`.
* 63 new test assertions (`tests/test_course_progress.php`).

= 0.11.0 =

* **Added: course certificates** ("Certificates" in the My Account nav, after Progress). Needs Core 1.12.0. Each certificate has a "View / print" button and a link to share.
* **Printable certificate page** at `/?efa_certificate=CODE`. It shows the learner's name, course, CEFR level badge, date issued and certificate ID. "Print / save as PDF" uses the browser's own print dialog, laid out for one landscape A4 page. The page also works as the verification link: anyone who opens it sees the certificate, and an unknown code shows "Certificate not found" with a 404. It is never cached or indexed, and it says the certificate is not an official qualification.
* **Fixed: course progress was never recorded.** All six CEFR courses are public in Tutor, so learners could read lessons and take quizzes without enrolling, and Tutor records nothing for learners who aren't enrolled. That meant no "Mark as Complete" button, no progress and no way to complete a course. Now a logged-in learner who opens a lesson or quiz of a free public course is enrolled automatically, through Tutor's own enrolment function. Paid courses are never auto-enrolled.
* Guests reading a lesson or quiz see a short note that logging in saves their progress and earns a certificate. The courses stay public.
* **Safety net:** opening My Account issues any missing certificate for a course that Tutor itself reports as completed.
* Certificates are included in WordPress's personal data export and erasure. Erasing a certificate also stops its link from verifying.
* The Progress section no longer says certificates are coming later. New `award` icon.
* New test assertions in `tests/test_certificates.php`. `test_progress.php` is updated for the removed footnote.

= 0.10.0 =

* **Added: weekly leaderboard section** ("Leaderboard" in the My Account nav, after Progress). It ranks learners by XP earned this week, Monday to Sunday in site time, and needs Core 1.11.0.
* The section shows the week's dates and days until the reset, then the top 10, with initials avatars and the top three marked.
* Joined learners see their own standing ("You are #5 of 12 · 25 XP this week"). If they're outside the top 10, their own row is added under it.
* **Opt-in only**, as the design requires. Nobody appears until they press "Join the leaderboard" or tick the existing profile box; both set the same setting. The Join card shows the exact name they'll appear under, with a link to change it. "Leave the leaderboard" removes them immediately. Nobody who hasn't joined is ranked or counted.
* **Members only**: the board is shown only inside My Account, never on a public page.
* **Email safety net**: names are WordPress display names, and anything that looks like an email address is cut to the part before the @, so an address can't be published. None of the site's 14,591 display names contains an @ (checked live).
* New `ProfileRepository::meta_key()`, so the opt-in's storage key is named in one place.
* 32 new test assertions (`tests/test_leaderboard.php`). `test_level.php`'s wiring check now ignores alignment spaces.

= 0.9.0 =

* **Added: daily goal.** The Profile's "Daily goal (minutes)" box is replaced by a goal choice in XP per day: Casual 10, Regular 20 (default), Serious 30, Intense 50, with a note on how XP is earned. The site records XP for every activity but never measured minutes, so a minutes goal could only ever have been invented. Nobody on the live site had changed the minutes value, so nothing needed converting.
* Progress opens with a **"Today's goal" ring**: XP today out of the goal, "Daily goal reached" once met, how many of the last 7 days hit the goal, and a "Change goal" link. Needs Core 1.10.0.
* **Added: streak calendar** in Progress. It shows five whole Monday-to-Sunday weeks ending with this week, with each day marked "no activity", "active" or "goal reached". Days later this week are shown as upcoming, not missed. A summary sentence carries it for screen readers.
* Days covered by a streak freeze are not marked, because Core doesn't record which days a freeze covered.
* The streak explanation now says practice answers count too, which they always did.
* Styles added for `<select>` fields and field hints on these pages.
* 36 new test assertions (`tests/test_daily_goal.php`). `test_account_foundation.php` and `test_progress.php` were updated for the new profile field and query.

= 0.8.0 =

* **Added: Mistake notebook section** ("Mistakes" in the My Account nav). It shows the questions a learner got wrong in the practice tools, newest first, and needs Core 1.9.0 plus English Finders Study 1.13.0.
* Each entry shows the skill, level and tool; the question; the learner's answer next to the right one; and the explanation where the tool has one. Reading entries include the passage, collapsed.
* Each entry has a nonce-protected "Got it" button, and a "Practise in ..." link only when that tool's page is actually published.
* Filter chips by skill. Shows 20 at a time, with the total stated.
* Entries clear themselves when the learner answers the same question correctly later.
* Shown in full to every account for now: the design lists "last 10 free, full history Pro", but billing isn't configured on the live site.
* **Privacy:** a new "Mistake notebook" exporter and eraser in WordPress's Privacy Tools.
* On phones, account section cards now use half the padding (16px instead of 32px), which gives text about 15% more width at 375px. Desktop is unchanged.
* 39 new test assertions (`tests/test_mistakes.php`), including real rendering with escaping, and a "Got it" attempt on another user's entry that changes nothing. `test_level.php`'s version check no longer pins an exact version.

= 0.7.0 =

* **Added: My Level section (Phase A4)**, first in the My Account nav: the learner's English Level Test result as a CEFR badge with a can-do description, grammar/vocabulary/reading levels on a six-step A1-C2 scale, earlier results, a retake link, and real progress through the six Tutor LMS CEFR courses (Tutor's own enrolment records plus `get_course_completed_percent()`). With no result yet it shows only the invitation to take the test, never a placeholder level; with neither a result nor a published `/english-level-test/` page it is absent.
* Reads Core 1.8.0's `level_results` service; degrades to absent with an older Core. Per-skill levels are shown to everyone for now although the design lists them as Pro, because billing is not configured on the live site.
* **Added: privacy export/erase for level test results**, as a separate "English Level Test results" exporter and eraser in WordPress's Privacy Tools.
* Progress section no longer says the CEFR level is "coming later"; only certificates remain deferred. New `chart` icon.
* 45 new test assertions (`tests/test_level.php`), including real rendering of the section template.

= 0.6.0 =

* **A visual pass across the whole My Account page, closing a specific gap against Duolingo's and 7ESL's account dashboards.** Compared our page directly against both (screenshots of a real Duolingo profile and a real 7ESL account overview) and found three concrete, closeable gaps: no navigation (the page was one long scroll, no left nav like 7ESL's), zero icons or an avatar anywhere (confirmed via a full grep of every template — every stat was a bare number), and passive empty states ("Nothing saved yet" as a sentence, not a prompt like Duolingo's "Add friends" card). Per-skill CEFR bars (7ESL) and the social graph (Duolingo) were deliberately NOT chased — both need unbuilt phases (A4, A6) and faking either would break this page's honesty principle.
* **Added a profile header:** an avatar (initials, e.g. "EF" for "English Finder" — no photo upload exists yet, and this beats a blank placeholder), "Member since" date, and a plan badge when Membership data is available.
* **Added a left-nav dashboard layout** (`.efa-account-layout`): Progress / Library / Profile / Membership / Your data as real anchored nav links, collapsing to a horizontal pill tab bar under 780px. Still one server-rendered page — no client routing, no new JS, plain `<a href="#section-id">` anchors.
* **Added a small inline-SVG icon set** (`Support\Icons`, seven hand-authored glyphs — no new dependency) on every stat tile, badge pill, and library list item.
* **Added a "Next up" badge-progress strip** to the Progress section: for each badge not yet earned, a real current/threshold progress bar (e.g. "4 / 7" toward the 7-day streak badge), the two closest to completion. Needed a small, additive companion change in Core — `BadgeCatalog::thresholds()` (English Finders Core 1.7.4) — since badge thresholds are Core's business rule, not something to duplicate here.
* **Turned the Library section's empty states into a call to action:** "Nothing saved yet" now sits inside a card with a "Browse games" button linking to `/games/` (new `Support\Urls::games()`), instead of only stating a fact.
* A real bug caught by an actual browser render, not by reading the CSS: the new "Browse games" button's white text was invisible against its own blue background, because the page's existing generic `.efa-account-section a` link-color rule (a class+element selector) has higher specificity than a single-class button selector and was silently repainting it blue-on-blue. Fixed by scoping the button's selector to `.efa-account-section .efa-empty-state__cta`. Caught during this release specifically because a static HTML/CSS preview of the real markup was rendered in an actual browser (desktop and mobile widths) before packaging — not just visual imagination from reading the source.
* 8 new EFC test assertions (`BadgeCatalog::thresholds()`), full Core suite 86/86. 13 new EFA test assertions across `test_progress.php` (next_badges math, including the earned-badge-exclusion path) and `test_library.php` (icons + CTA wiring); full EFA suite 63 + 16 + 21 + 28 + 55 = 183/183.
* Not run through the Novamira `check-design` pre-flight this release (no new colors were introduced — every color in the new CSS is an existing `--efa-*` token, `#fff`, or an already-declared shadow/rgba value) — verified instead by rendering the real markup and CSS in an actual browser at desktop and mobile widths, which is what caught the blue-on-blue bug above.

= 0.5.0 =

* **Added: the My Library section of My Account (Phase A3, continued).** Shows saved words (favorites) and recent searches, read from Word Games Pro's existing `wuc_user_data` table through a new public facade WGP exposes for the first time (`WordUnscrambleCheats\Support\Api`, added in WGP 2.12.12) — mirrors the exact `class_exists()`+`is_at_least()`+`service()` pattern already used for every Core call site in this plugin, so this is the first time WGP has been a service *provider* to another plugin rather than only a *consumer* of Core. New `Library\LibraryController`, gated on WGP 2.12.12+; renders nothing when WGP isn't active or isn't new enough, the same honesty principle every earlier section of this page has applied.
* Item labels are derived from the repository's own `item_key` (split into "Tool: value"), not the saved payload's internal fields — each WGP tool's client-side JS defines its own payload shape when it calls the generic save endpoint, so it isn't centrally typed and can't be relied on to always carry the same keys.
* Deliberately does not stub a review queue or mistake notebook — nothing in this project tracks a wrong answer yet, so there is no real data to show for either.
* Updated the Progress section's "coming in a later update" line to drop "saved words," since that is now built.
* 15 new test assertions in `tests/test_library.php` (loads WGP's real `Support\Api`/`Plugin`/`Container`, with a fake `UserDataRepository`-shaped object registered on the real container); full suite re-run clean: 63 + 15 + 21 + 17 + 55 = 171/171.

= 0.4.1 =

* **Added: real visual design for the whole My Account page.** Every page shipped since Phase A1 rendered as unstyled default HTML; this adds `assets/css/account.css`, enqueued only on `/my-account/` (both the logged-in and logged-out views).
* **Not invented from scratch — captured from the site's own live design.** Read the actual rendered CSS from the homepage, the `/games/` card grid, and a CEFR word-list page before writing anything: Lexend (the site's real font, not a generic default), `#075AAE` (the site's one existing accent, used on every button and link already), `#0E2A4A` headings, white 16px-radius cards with the exact subtle shadow the site's own game/tool cards use, 5px-radius buttons matching the homepage CTA. Saved as a Novamira design (`english-finders-captured-brand`) and checked against it before shipping — the design intentionally holds a low `variance`/`motion` dial, since this is a returning-user dashboard, not a marketing page.
* Covers every section: login/register cards, the Progress stat tiles and badge pills, Profile/Privacy form fields, the Membership section's plan display and transaction table, and success/error notices (new, restrained green/red pair, used only for this purpose).
* 2 new test assertions confirming the stylesheet is enqueued (gated the same way as the template swap) and that the file exists; full suite re-run clean: 63 + 21 + 17 + 55 = 156/156. Verified against the real local WordPress install: the stylesheet `<link>` tag renders with the correct URL and version, and the file itself serves with the correct `text/css` content type and no PHP errors.

= 0.4.0 =

* **Added: the Progress section of My Account (Phase A3, scoped narrowly).** Shows total XP, current and longest streak, available streak freezes, and earned badges — all read from Core's Activity service (Phase A2), which now has three real emitters live (Daily Wordle, English Finders Study's quiz tools, Tutor LMS/QSM). New `Progress\ProgressController`, mirroring `Membership\MembershipController`'s shape and the same `class_exists()`+`is_at_least()` degrade-gracefully guard.
* **Deliberately not the full nine-section design.** A CEFR level badge needs the level test (Phase A4, unbuilt); the opt-in weekly leaderboard needs ranking across all users (Phase A6); a saved-words library needs a new read path into Word Games Pro's own data (not yet built). Rather than stub dead links or "coming soon" panels for these, the section states plainly what's coming later and shows only what's real — the same honesty principle Phase A1 established for the rest of this plugin.
* Minimum Core version raised to 1.7.0 (where the `activity` service was added) — see `= Requirements =` above.
* New Core dependency: `BadgeCatalog::label()`/`::description()` (English Finders Core 1.7.3) for human-readable badge display, rather than hardcoding a second copy of the catalog in this plugin.
* 17 new test assertions (`tests/test_progress.php`), loading Core's real Activity source against a fake `$wpdb` — not a reimplementation — full suite re-run clean: 61 + 21 + 17 + 55 = 154/154. Verified against the real local WordPress install with genuinely accumulated data from earlier testing: a real user with 165 real XP and two genuinely-earned badges (`first_activity`, `xp_100`) rendered correctly through the full page (get_header()/get_footer() intact, no regression of the 0.2.1 footer bug), and a brand-new user correctly showed the zero/empty state.

= 0.3.1 =

* **Fixed: real users, including brand-new registrations, could be intermittently unable to log in via `/my-account/` with the correct password.** Root cause, confirmed live via Novamira: Wordfence Login Security enforces its own CAPTCHA requirement inside `wp_signon()`'s `authenticate` filter chain (`WordfenceLS\Controller_WordfenceLS::_authenticate`, priority 25) -- this form never rendered Wordfence's own CAPTCHA widget or sent the token it checks for, so Wordfence rejected the attempt with its own `WP_Error` (`wfls_captcha_verify`) even when the password was completely correct. This plugin's own generic `invalid_login` error message then masked the real cause. The intermittent "sometimes it works" pattern traced back to Wordfence's email-verification workaround: a rejected attempt gets emailed a verification link, and visiting it temporarily satisfies the CAPTCHA requirement until it expires again.
* **Fix:** the login form now renders the same Cloudflare Turnstile widget already used on registration, and `LoginHandler` verifies it before calling `wp_signon()`. Once Turnstile is configured (it already is, live, via the `EFA_TURNSTILE_SITE_KEY`/`EFA_TURNSTILE_SECRET_KEY` wp-config constants set during 0.3.0), `LoginHandler` also adds `add_filter( 'wordfence_ls_require_captcha', '__return_false' )`, scoped to that single request only -- Wordfence's own CAPTCHA is suppressed on this route specifically because Turnstile is now providing equivalent protection, not removed unconditionally. Every other login path (native `wp-login.php`, XML-RPC) keeps Wordfence's CAPTCHA exactly as configured.
* If Turnstile is ever unconfigured, the Wordfence-suppression filter is never added either -- the fix only relaxes Wordfence's requirement when something is actually replacing it.
* 8 new test assertions covering the login-Turnstile and Wordfence-filter wiring, full suite re-run clean: 61 + 21 + 55 = 137/137. Verified against the same real local WordPress install used for every prior release: a real user with the correct password now logs in successfully (302 to `/my-account/`, no error), and a wrong password still correctly fails with `invalid_login` -- confirming no regression to the core login flow when Turnstile is unconfigured locally.

= 0.3.0 =

* **Added: registration anti-bot hardening.** Registration had zero real bot protection -- `RegistrationHandler` called `wp_insert_user()` directly, which bypasses WordPress's `registration_errors` filter entirely (so even Wordfence's own, already-active registration check never ran against this form). Three independent layers, all new:
  - **Honeypot field** — a CSS-hidden, `aria-hidden`, `tabindex="-1"` field (`efa_hp_website`) real users never see or fill. A bot that fills every field trips it; the request is rejected with the same generic `invalid_email` code a normal validation failure would use, so nothing about the honeypot's existence leaks back.
  - **IP-based rate limiting** — `Security\RegistrationThrottle`, transient-backed, 5 registration attempts per IP per 15-minute window, counted regardless of whether each attempt succeeds or fails (a bot that succeeds every time is exactly the case this exists to stop). Best-effort IP resolution checks `CF-Connecting-IP` and `X-Forwarded-For` before falling back to `REMOTE_ADDR`.
  - **Cloudflare Turnstile** — `Security\TurnstileVerifier`, dormant until both a site key and secret key are configured. This project has no settings admin screen yet, so both keys are set as wp-config.php constants (`EFA_TURNSTILE_SITE_KEY`, `EFA_TURNSTILE_SECRET_KEY`), the same way every other credential here is (Paddle's API key, webhook secret, OpenRouter's key) -- each takes precedence over the `efa_settings` option, which is currently only reachable by a future admin screen or a direct option write. The widget script only enqueues on the logged-out My Account page once configured. Verification **fails open** on a Cloudflare API/network error -- a third-party outage must not itself block real registrations, since the honeypot and rate limit keep working regardless.
* New `Security` namespace (`RegistrationThrottle`, `TurnstileVerifier`) and `Support\Settings` (this plugin's own settings option, `efa_settings` -- kept separate from Core's `efc_settings` since Turnstile is a registration concern local to this plugin).
* 47 new test assertions (`tests/test_registration_security.php`), full suite re-run clean: 61 + 21 + 47 = 129/129. Verified against the same real local WordPress install used for prior releases -- honeypot-filled submissions create no user, legitimate submissions succeed and log the user in, and the 6th registration attempt from one IP within the window is correctly blocked with no PHP errors in `debug.log`. One real bug caught by that runtime test and not by the standalone suite: an edit had accidentally dropped the `namespace EnglishFindersAccount\Core;` line from `Activator.php`, which the string-based tests didn't catch (the string `Settings::seed_defaults()` was still present) but a real `class_exists()`/`is_callable()` check would have -- that check was added to the test suite alongside the fix.
* No change to login -- Wordfence's real brute-force protection already covers it meaningfully, unlike its trivial (username-"admin"-only) registration check.

= 0.2.1 =

* **Fixed: the site's Elementor-built global footer broke on `/my-account/` after 0.2.0 deployed live.** Root cause: `MyAccountController` rendered the page via `template_redirect` + manual `exit`, which skips WordPress's normal `template-loader.php` sequence entirely -- the exact sequence Elementor's Theme Builder hooks into to decide whether to inject its custom footer. Confirmed live (2026-09-22): another plain classic page on the same site, `/privacy-policy/` (not built with Elementor either, same as my-account), correctly got the full Elementor footer -- so the difference was never "classic vs Elementor content," it was specifically the `template_redirect`-based rendering bypassing the lifecycle Elementor depends on.
* Switched to the `template_include` filter instead -- WordPress's actual intended mechanism for "swap the template file, keep the rest of the normal request lifecycle intact," and also what Elementor's own full-page templates use internally. New file: `templates/public/my-account-template.php`, which now calls `get_header()`/`get_footer()` as part of a normal template inclusion rather than a short-circuited one. `MyAccountController` is now just the routing filter; the logged-in/logged-out branching logic that used to live there moved into the new template file.
* No functional change to registration, login, profile, privacy requests, or the Membership section -- re-verified against the same real WordPress install used for 0.2.0's testing, all flows still pass.

= 0.2.0 =

* Added: Membership & Billing section on the My Account page (Phase A5) — current plan/status/renewal date (`EntitlementRepository::for_user()`), a "Manage billing" button that opens a Paddle Customer Portal session for a paid subscriber, and a same-site transaction history read from Core's `billing_events` log.
* Added: `Requires Plugins: english-finders-core` — the first real dependency on Core. `MembershipController` guards every call with a `class_exists()` + `Api::is_at_least('1.6.0')` check (the same pattern Word Games Pro already uses at its own Core call sites), so a missing or too-old Core degrades to the section simply not rendering rather than a fatal error.
* Added: checkout — "Upgrade" buttons open Paddle's own checkout overlay (Paddle.js) for a free-plan user, passing `custom_data.user_id` so the resulting webhook attaches to the correct account without a separate claim step. Uses the sandbox price IDs already created and tested during Phase A0's live verification, clearly not real prices — see `a5-billing-ui.md`.
* Deliberately not built: any custom change-plan/cancel/proration flow. Paddle's own Customer Portal handles that; see `a5-billing-ui.md` for why this project isn't re-deciding proration policy to build a parallel UI for it.
* Real Paddle credentials (API key, client-side token) are not included — same "never in chat, added directly to config" handling as the Phase A0 webhook secret.

= 0.1.0 =

* Initial release (Phase A1 — account foundation). New plugin.
* Registration and login via native WordPress (`wp_insert_user()`, `wp_signon()`, `wp_set_auth_cookie()`) — no parallel auth system, no reinvented password handling.
* Profile fields (`native_language`, `daily_goal_minutes`) and four privacy/notification toggles (`public_profile`, `leaderboard_optin`, `notifications_enabled`), stored as usermeta, all defaulting to private/off.
* `/my-account/` (page ID 7276 as of the 2026-09-21 site audit, matched by slug not ID) intercepted via `template_redirect`: logged-out visitors see a combined login/registration form; logged-in users see the profile/privacy/data-request shell.
* Self-service data export and account-deletion requests, both registered with WordPress core's own Privacy Tools (`wp_privacy_personal_data_exporters`/`_erasers`) and both routed through `wp_create_user_request()` + `wp_send_user_request()` — a confirmation email is required before anything happens, the same as core's own admin-initiated flow.
* Anti-abuse: relies on Wordfence Security (already active on the live site) for login brute-force protection rather than building a second, weaker layer. No CAPTCHA or custom throttling in this release.
* Minors/consent handling is explicitly unresolved — not addressed in this release. Every privacy default is off/private in the meantime.
