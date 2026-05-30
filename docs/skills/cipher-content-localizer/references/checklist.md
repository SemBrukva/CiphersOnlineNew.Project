# Final Checklist

1. File name matches target locale (e.g. `*.ru.json`).
2. `meta.language` equals target locale.
3. `meta.default_language` unchanged from source.
4. All IDs preserved.
5. `blocks[].data.text` keeps valid HTML (`<p>`/lists).
6. `examples[].data` has non-empty `title`, `key`, `input`, `output`.
7. `output` values were recomputed from API, not translated.
8. Semantic intent of each example preserved in localized version.
9. JSON parses successfully.
