# AI Writing Feedback: page and activation

Scripts for the `/writing-feedback/` page (Study 1.18.0's `[efs_writing_feedback]` tool, Core 1.16.0's `ai` service). Send each file's body, without the opening `<?php` line, as the `code` of Novamira `novamira/execute-php`.

| Script | What it does |
|---|---|
| `page.php` | Builds or rebuilds page 35949 `/writing-feedback/`, in the same Elementor layout as the other tool pages: the tool, an explainer, the FAQ, then `[efs_related_tools]`. It also writes the Rank Math title and description and purges the page by ID. The daily limits in the copy are read from Core's settings. |
| `activate.php` | Run once the OpenRouter account has credits. It makes one real test request. **Only if that request succeeds** does it switch `ai_enabled` on, publish the page (a draft until then), add the tool's card to the Practice hub (34543) after Sentence Builder, and purge those pages plus `/pricing/`. Safe to re-run. |

After `activate.php`, rebuild the Writing hub so it shows the new card: send `tools/learn-hubs/lib.php` + `levels.php` + `skill_pages.php` with `$efl_only = array( 'writing' );` prepended.

## Status (2026-09-29)

All three plugins are deployed and AI is **off**. The OpenRouter key's account has no credits (402 "Insufficient credits"), and OpenRouter's free models are not available. Speech generation uses the same key, and its newest audio file is dated 2026-08-25.

## Costs and limits

- The default model, `anthropic/claude-haiku-4.5`, costs about half a US cent per check.
- Every account gets 3 checks a day, Pro 30 a day, and the whole site 300 a day, so the worst case is about $1.50–2.60 a day.
- All limits can be changed in Settings → English Finders Core → AI features.
