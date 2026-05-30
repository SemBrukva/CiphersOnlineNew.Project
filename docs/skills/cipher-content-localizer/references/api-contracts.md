# API Contracts For Example Recompute

## Endpoint
`POST /api/tools/{cipher_alias}`

Base URL in local dev is typically `http://127.0.0.1:8080`.

## Generic payload shape
```json
{
  "text": "...",
  "direction": "encrypt",
  "locale": "ru",
  "settings": {
    "key": "...",
    "alphabet": "auto"
  }
}
```

## Response shape (success)
```json
{
  "ok": true,
  "result": "...",
  "alphabet": "...",
  "key": "..."
}
```

## Playfair specifics
- Requires non-empty `settings.key`.
- Supports `direction` in `encrypt|decrypt`.
- `alphabet=auto` is acceptable and usually preferred for localized examples.

## Validation strategy used by script
1. For each source example, try both directions on source `key/input`.
2. If one direction reproduces source `output`, freeze this direction for the localized version.
3. Recompute localized `output` by calling API with localized `key/input` and frozen direction.
4. If no direction matches source output exactly, fallback to `encrypt` and emit a warning.
