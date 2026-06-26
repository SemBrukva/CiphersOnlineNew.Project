---
name: semantic-core-builder
description: Build CiphersOnline semantic-core JSON files from semantic-raw CSV groups, analyze search phrases into primary/secondary/long-tail queries, detect intents/modifiers, and prepare agent-ready SEO guidance for multilingual tool content.
---

# Semantic Core Builder

Use this skill when creating or updating files under:

- `private/storage/semantic-raw/{locale}/{category}/{tool}.csv`
- `private/storage/semantic-core/{locale}/{category}/{tool}.json`

## Workflow

1. Inspect the raw CSV and adjacent meta file:
   - CSV: `query;score;competitiveness`
   - Meta: `{tool}.meta.json` with `schema`, `locale`, `source`, `score_metric`, `cluster`, `tool`, `imported_at`.
2. Run the deterministic importer:

   ```bash
   php bin/console semantic:raw:import private/storage/semantic-raw/{locale}/{category}/{tool}.csv --force
   ```

3. Review the generated JSON:
   - `status` should remain `draft` until a human/agent has reviewed priorities and targets.
   - Keep JSON as the curated source of truth. Database sync must consume this JSON, not raw CSV.
4. Run:

   ```bash
   php bin/console semantic:validate
   ```

5. If the semantic-core JSON was changed manually, keep valid JSON formatting and preserve `schema: semantic-core.v1`.

## Analysis Rules

Treat `score` as a source-specific weight, not universal search volume. Its meaning comes from `meta.score_metric`, so do not rename it to `volume` unless the source truly provides comparable volume.

Priorities:

- `primary`: the main query the page should target. Usually the highest score and closest match to the cluster.
- `secondary`: high-value alternate phrasings, encode/decode variants, locale variants, and major modifier groups.
- `long_tail`: low-score specific variants that can be covered by FAQ, examples, or supporting text.

Common intents:

- `tool`: generic tool intent when no narrower action is detected.
- `cipher`: explicit cipher/code/system intent.
- `translate`: generic translator intent.
- `decode`: phrases like “с азбуки”, “из азбуки”, “на русский”, “в текст”, “расшифровать”.
- `encode`: phrases like “на азбуку”, “в азбуку”, “с русского”, “текст в”.
- `audio`: phrases about sound/listening.
- `online`: explicit online modifier.
- `english`: cross-language English modifier.

Targets:

- `meta_title`: one primary query only unless a page has multiple equivalent heads.
- `intro`: main encode/decode variants and user-visible tool promise.
- `content_block`: explanatory or supporting semantic groups.
- `faq`: long-tail, audio, edge cases, and question-like variants.
- `examples`: phrases implying practical conversion examples.

## Quality Bar

The generated JSON should help an agent write or revise the linked content file, not just store keywords. Preserve:

- `analysis.primary_terms`
- `analysis.modifiers`
- `analysis.intents`
- `analysis.content_recommendations`
- full `queries` list with `score`, `competitiveness`, `priority`, `intent`, and `target`

Do not import obvious unrelated clusters into the curated JSON. If raw data contains mixed intent groups that deserve separate services, keep them in the JSON as `long_tail` only when they support the current tool; otherwise note the split opportunity in `notes`.
