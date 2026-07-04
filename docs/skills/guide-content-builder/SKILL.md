---
name: guide-content-builder
description: Create, localize, update, review, and validate multilingual CiphersOnline guides under private/storage/content/guides. Use when Codex needs to add a guide or article, prepare all supported locale JSON files, update guide relationships, revise an existing guide, or review guide content against guide-article.v1.
---

# Guide Content Builder

Build useful long-form CiphersOnline articles in every supported locale. Treat Git-tracked JSON as the source of truth and keep each guide publishable as one complete multilingual unit.

## Establish the contract

1. Work from the CiphersOnline repository root and read `AGENTS.md`.
2. Read `docs/guide-article-json.md` as the implemented file contract.
3. Read the relevant entry in `docs/plans/guides-spec.md` when the requested slug appears there. Preserve its canonical slug and localized titles unless the user requests a deliberate change.
4. Inspect:
   - `private/storage/content/guides/` for house style and existing relationships;
   - `private/config/locale.php` for the authoritative locale set;
   - `private/config/guide_related.php` for tool-to-guide links;
   - relevant tool content under `private/storage/content/{category}/{tool}/`;
   - relevant glossary entries under `private/storage/content/glossary/`.
5. When semantic-core files exist for the topic or related tools, use them to understand search intent and avoid competing with a tool page. Do not force unrelated keywords into the article.

Follow implemented code and the JSON contract when an older planning note conflicts with them. There is no locale fallback: every configured locale file is required.

## Plan the article

Before writing files, settle:

- one immutable canonical slug matching `[a-z0-9-]+`;
- one category: `how-to`, `deep-dive`, `history`, or `lists`;
- the reader's primary question and the article's boundary;
- a teaching outline with non-overlapping sections;
- real related tools, glossary terms, and published or concurrently created guides;
- publication state for the complete locale set.

Keep the article distinct from related pages. A guide must answer a broader informational intent; it must not duplicate a tool's form instructions or a glossary term's short definition.

Read [references/content-quality.md](references/content-quality.md) before authoring or reviewing prose.

## Author the canonical version

Create `private/storage/content/guides/{slug}/en.json` first as the semantic reference.

- Set `meta.schema` to `guide-article.v1`.
- Make `meta.guide_slug` equal the directory name and `meta.language` equal `en`.
- Use the current date for a new guide's `updated_at`. Set `published_at` only on first publication; preserve it during later edits.
- Write a specific title, a one- or two-sentence excerpt, distinct SEO fields, and a realistic integer `reading_time`.
- Structure the body as a logical progression. Use `sort_order` values in increments of 10 and keep headings in `body[].title`.
- Prefer concrete worked examples, limitations, common mistakes, and decision guidance over generic introductions.
- Add FAQ only for genuine follow-up questions not already answered verbatim in the body.
- Use only trusted HTML allowed by the contract. Keep links intentional and verify their targets exist.
- Add a small set of stable tags; do not use keyword variants as tags.

Verify cryptographic calculations and examples with the relevant project API or tool implementation when practical. For historical, security-sensitive, modern-standard, or otherwise uncertain claims, consult authoritative primary sources before writing.

## Localize the complete set

Create one complete file for every locale configured by the project. At the time this skill was created, the set is `en`, `ru`, `de`, `es`, `fr`, `it`, `pt`, and `tr`; configuration is authoritative.

For each locale:

1. Preserve the schema, canonical slug, category, relationship slugs, article scope, and factual meaning.
2. Set `meta.language` to the filename locale.
3. Localize the title, excerpt, SEO fields, headings, prose, FAQ, tags where appropriate, and relationship labels.
4. Adapt alphabet-dependent explanations, frequencies, examples, terminology, punctuation, and reader assumptions. Do not merely translate an English-language example when it becomes less useful or misleading.
5. Keep coverage aligned while allowing each version to read as independently authored prose.
6. Recalculate `reading_time` when localized length differs materially.
7. Prefer structured `related_tools` links over repeated inline promotion. For necessary inline internal links, preserve the active locale by using the repository's localized URL convention; do not send non-English readers silently to the default locale.

Write readable UTF-8 JSON with four-space indentation and unescaped Unicode, matching existing content files.

## Connect the article

Use relationships for useful next steps, not keyword accumulation.

- Add `related_tools` only for existing `category/tool` directories and localize each label.
- Add `related_terms` only for existing glossary directories or terms created in the same task.
- Add `related_guides` only for existing guides or guides created in the same task.
- Update `private/config/guide_related.php` when a relevant tool should link back to the guide. Keep the list narrow and ordered.
- Add reciprocal guide links only when they materially improve navigation and are within task scope.

Do not modify unrelated content solely to make the new article appear more connected.

## Apply publication policy

Keep all locale files at the same status:

- use `published` only when every locale is complete, factual checks pass, links resolve, and validation succeeds;
- use `draft` for the whole locale set when any version is provisional or the user requests a draft.

Never publish a partially localized guide. On updates, preserve the original `published_at` across every locale and change `updated_at` consistently.

## Validate and review

Run:

```bash
php bin/console guides:validate
```

Then inspect the diff and check:

- every configured locale is present and valid JSON;
- slugs, category, status, dates, relationships, and article coverage agree across locales;
- examples and calculations are correct;
- no English fragments or translationese remain unintentionally;
- internal inline links retain the reader's locale and do not duplicate the related-links panel excessively;
- SEO fields describe the article naturally without cannibalizing the linked tool page;
- trusted HTML is balanced and contains no scripts, styles, event attributes, or speculative links;
- `guide_related.php` contains only deliberate reverse links.

If PHP code or config changed beyond relationship-only content wiring, run proportionate PHPUnit tests as well.

Report the created or updated guide, locale coverage, publication status, relationships changed, factual or API checks performed, and validation result.
