---
name: cipher-content-localizer
description: Use this skill when you need to localize cipher content JSON files (for example classical-ciphers.playfair.en.json to *.ru.json) with context-aware adaptation, not literal translation, and with mandatory recalculation of localized example outputs through the project API.
---

# Cipher Content Localizer

## Overview
This skill localizes cipher page JSON content from a source language (usually `en`) into a target locale while preserving schema integrity and semantic intent. It explicitly adapts example scenarios and recomputes `examples[].data.output` via `/api/tools/{cipher}` instead of translating examples literally.

## When To Use
Use this skill when all are true:
- You already have a finalized source content JSON (typically `*.en.json`).
- You need a target localized JSON file (for example `*.ru.json`).
- Examples must remain cryptographically valid for the target language.

Do not use this skill for direct DB writes. This skill prepares localized JSON artifacts for later import.

## Inputs
Required inputs:
- Source JSON path (example: `private/storage/content/classical-ciphers.playfair.en.json`)
- Target locale code (example: `ru`)
- Target JSON path

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
3. Localize text blocks with adaptation, not literal translation:
- `cipher_translation`
- `blocks[].data.title/text`
- `faq[].data.question/answer`
- `tags[].data.tag`
- `examples[].data.title/description`
4. Adapt examples for target locale:
- Replace `input` with natural target-language text matching the educational goal of the example.
- Replace `key` with a valid key for the same alphabet.
- Preserve the teaching purpose of each example (basic encryption, repeated letters, etc.).
5. Recompute every `examples[].data.output` via API using script `scripts/recompute_example_outputs.py`.
6. Validate final JSON:
- valid JSON
- schema shape preserved
- all examples contain non-empty `key/input/output`
- API recomputation succeeded without unresolved examples

## Localization Rules
- Prioritize clarity and pedagogical value over literal phrasing.
- Keep cryptography terminology consistent for the target locale.
- For `blocks[].data.text`, keep HTML structure intact (`<p>`, optional multiple paragraphs, lists allowed).
- Do not remove or alter entity `id` values.
- Do not add new entities in non-default language files.

## Example Handling Rules
- Never translate example `output` directly.
- Always derive output through API from localized `input` + localized `key`.
- If an example appears to be decrypt-focused, preserve that intent. The recompute script infers direction (`encrypt`/`decrypt`) by matching source behavior first, then applies that direction to localized values.

## Scripts
- `scripts/recompute_example_outputs.py`

Usage:
```bash
python3 scripts/recompute_example_outputs.py \
  --source /abs/path/classical-ciphers.playfair.en.json \
  --target /abs/path/classical-ciphers.playfair.ru.json \
  --base-url http://127.0.0.1:8080
```

Optional flags:
- `--write-directions` adds `meta.example_directions` for debugging.

## References
- `references/api-contracts.md` for API payloads and expectations.
- `references/checklist.md` for final QA checklist.
