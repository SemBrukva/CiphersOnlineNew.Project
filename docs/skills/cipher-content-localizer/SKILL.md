---
name: cipher-content-localizer
description: Use this skill when you need to localize cipher content JSON files (for example classical-ciphers/playfair/en.json to ru.json) with context-aware adaptation, not literal translation, and with mandatory recalculation of localized example outputs through the project API.
---

# Cipher Content Localizer

## Overview
This skill localizes cipher page JSON content from a source language (usually `en`) into a target locale while preserving schema integrity and semantic intent. It explicitly adapts example scenarios and recomputes `examples[].data.output` via `/api/tools/{cipher}` instead of translating examples literally.

When a matching semantic-core file exists, this skill must use it as SEO and user-intent context for the target locale. Semantic-core complements the current tool behavior; it must not override, exaggerate, or replace what the service actually supports.

## When To Use
Use this skill when all are true:
- You already have a finalized source content JSON (typically `*.en.json`).
- You need a target localized JSON file (for example `*.ru.json`).
- Examples must remain cryptographically valid for the target language.

Do not use this skill for direct DB writes. This skill prepares localized JSON artifacts for later import.

## Inputs
Required inputs:
- Source JSON path (example: `private/storage/content/classical-ciphers/playfair/en.json`)
- Target locale code (example: `ru`)
- Target JSON path

Optional but required when present:
- Semantic-core JSON path derived from the target file:
  `private/storage/semantic-core/{target_locale}/{category_alias}/{cipher_alias}.json`

Runtime prerequisites:
- Local API is available (usually `http://127.0.0.1:8080`)
- Source file follows `cipher-content.v1`

## Output
A target file with the same schema:
- `meta.language` set to target locale
- Content localized for target audience
- `examples[].data.key/input/output/description` adapted for target locale
- `examples[].data.output` recomputed via API for each example

## Workflow
1. Duplicate source JSON into target JSON path.
2. Update `meta.language` to target locale and keep `meta.default_language` unchanged.
3. If semantic-core exists for the target locale and tool, read it before writing localized text.
4. Localize text blocks with adaptation, not literal translation:
- `cipher_translation`
- `blocks[].data.title/text`
- `faq[].data.question/answer`
- `tags[].data.tag`
- `examples[].data.title/description`
5. Adapt examples for target locale:
- Replace `input` with natural target-language text matching the educational goal of the example.
- Replace `key` with a valid key for the same alphabet.
- Preserve the teaching purpose of each example (basic encryption, repeated letters, etc.).
6. Recompute every `examples[].data.output` via API using script `scripts/recompute_example_outputs.py`.
7. Validate final JSON:
- valid JSON
- schema shape preserved
- all examples contain non-empty `key/input/output`
- API recomputation succeeded without unresolved examples

## Semantic-Core Rules
- Derive the semantic-core path from the target content path:
  `private/storage/content/{category_alias}/{cipher_alias}/{locale}.json`
  → `private/storage/semantic-core/{locale}/{category_alias}/{cipher_alias}.json`.
- If the file exists, read `cluster`, `status`, `analysis.query_groups`, `analysis.content_recommendations`, `queries`, and `curation` before localizing.
- Use semantic-core to shape wording, emphasis, SEO fields, FAQ coverage, and examples.
- Do not promise features that the current tool, API, or UI does not provide. Existing functionality is the hard boundary; semantic-core is demand context.
- Preserve the page's actual purpose. For example, if semantic-core contains audio queries but the tool can only play/generated Morse audio, mention listening/playback/download if supported, but do not claim audio recognition or uploading sound unless implemented.
- Use `primary` queries for `meta_title`, main name/description, and first-screen wording. Usually keep the exact primary phrase in `meta_title` when natural.
- Use `secondary` queries and `query_groups` for intro, descriptions, major blocks, and core FAQ.
- Use `long_tail` queries for FAQ, examples, or short supporting explanations. Do not overload `meta_title`/H1 with long-tail variants.
- Use `analysis.content_recommendations` as editorial guidance, not text to paste verbatim.
- If semantic-core conflicts with source content or functionality, keep functionality correct and add a brief note in the final response describing the conflict.

## Localization Rules
- Prioritize clarity and pedagogical value over literal phrasing.
- Keep cryptography terminology consistent for the target locale.
- For `blocks[].data.text`, keep HTML structure intact (`<p>`, optional multiple paragraphs, lists allowed).
- Do not remove or alter entity `id` values.
- Do not add new entities in non-default language files.
- Avoid keyword stuffing. Semantic coverage means natural coverage of user intents, not mechanical repetition of all queries.

## Example Handling Rules
- Never translate example `output` directly.
- Always derive output through API from localized `input` + localized `key`.
- If an example appears to be decrypt-focused, preserve that intent. The recompute script infers direction (`encrypt`/`decrypt`) by matching source behavior first, then applies that direction to localized values.

## Scripts
- `scripts/recompute_example_outputs.py`

Usage:
```bash
python3 scripts/recompute_example_outputs.py \
  --source /abs/path/classical-ciphers/playfair/en.json \
  --target /abs/path/classical-ciphers/playfair/ru.json \
  --base-url http://127.0.0.1:8080
```

Optional flags:
- `--write-directions` adds `meta.example_directions` for debugging.

## References
- `references/api-contracts.md` for API payloads and expectations.
- `references/checklist.md` for final QA checklist.
