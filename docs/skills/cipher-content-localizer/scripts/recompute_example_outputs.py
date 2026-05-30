#!/usr/bin/env python3
"""Recompute localized example outputs using cipher API.

This script infers each example direction from the source JSON, then recomputes
output in target JSON using localized key/input with the same direction.
"""

from __future__ import annotations

import argparse
import json
import sys
import urllib.error
import urllib.request
from pathlib import Path
from typing import Any


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def post_json(url: str, payload: dict[str, Any]) -> dict[str, Any]:
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        raw = resp.read().decode("utf-8")
    return json.loads(raw)


def normalize(value: str) -> str:
    return "".join(value.split())


def infer_direction(
    base_url: str,
    cipher_alias: str,
    locale: str,
    key: str,
    text: str,
    expected_output: str,
) -> str:
    endpoint = f"{base_url}/api/tools/{cipher_alias}"
    for direction in ("encrypt", "decrypt"):
        payload = {
            "text": text,
            "direction": direction,
            "locale": locale,
            "settings": {"key": key, "alphabet": "auto"},
        }
        try:
            response = post_json(endpoint, payload)
        except urllib.error.URLError:
            continue

        if not response.get("ok"):
            continue

        result = str(response.get("result", ""))
        if result == expected_output or normalize(result) == normalize(expected_output):
            return direction

    return "encrypt"


def recompute(
    base_url: str,
    cipher_alias: str,
    locale: str,
    direction: str,
    key: str,
    text: str,
) -> str:
    endpoint = f"{base_url}/api/tools/{cipher_alias}"
    payload = {
        "text": text,
        "direction": direction,
        "locale": locale,
        "settings": {"key": key, "alphabet": "auto"},
    }
    response = post_json(endpoint, payload)
    if not response.get("ok"):
        raise RuntimeError(f"API failed: {json.dumps(response, ensure_ascii=False)}")
    return str(response.get("result", ""))


def main() -> int:
    parser = argparse.ArgumentParser(description="Recompute localized example outputs.")
    parser.add_argument("--source", required=True, help="Source (usually en) JSON path")
    parser.add_argument("--target", required=True, help="Target localized JSON path")
    parser.add_argument("--base-url", default="http://127.0.0.1:8080", help="App base URL")
    parser.add_argument("--write-directions", action="store_true", help="Write inferred directions into meta")
    args = parser.parse_args()

    source_path = Path(args.source)
    target_path = Path(args.target)

    source = load_json(source_path)
    target = load_json(target_path)

    source_examples = source.get("examples", [])
    target_examples = target.get("examples", [])

    if not isinstance(source_examples, list) or not isinstance(target_examples, list):
        raise RuntimeError("Invalid examples format in source/target")

    source_by_id = {}
    for item in source_examples:
        if not isinstance(item, dict):
            continue
        eid = int(item.get("id", 0) or 0)
        if eid > 0:
            source_by_id[eid] = item

    target_locale = str((target.get("meta") or {}).get("language", ""))
    cipher_alias = str((target.get("meta") or {}).get("cipher_alias", ""))

    if not target_locale or not cipher_alias:
        raise RuntimeError("target meta.language and meta.cipher_alias are required")

    inferred: dict[str, str] = {}

    for item in target_examples:
        if not isinstance(item, dict):
            continue

        eid = int(item.get("id", 0) or 0)
        data = item.get("data") or {}
        if not isinstance(data, dict):
            continue

        key = str(data.get("key", "")).strip()
        text = str(data.get("input", "")).strip()

        if not key or not text:
            raise RuntimeError(f"Example id={eid} has empty key/input")

        direction = "encrypt"
        source_item = source_by_id.get(eid)
        if source_item:
            src_data = source_item.get("data") or {}
            if isinstance(src_data, dict):
                src_key = str(src_data.get("key", "")).strip()
                src_input = str(src_data.get("input", "")).strip()
                src_output = str(src_data.get("output", "")).strip()
                if src_key and src_input and src_output:
                    direction = infer_direction(
                        args.base_url,
                        cipher_alias,
                        str((source.get("meta") or {}).get("language", "en")),
                        src_key,
                        src_input,
                        src_output,
                    )

        output = recompute(args.base_url, cipher_alias, target_locale, direction, key, text)
        data["output"] = output
        inferred[str(eid)] = direction

    if args.write_directions:
        meta = target.setdefault("meta", {})
        if isinstance(meta, dict):
            meta["example_directions"] = inferred

    target_path.write_text(
        json.dumps(target, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
    )

    print(f"Updated {len(target_examples)} examples in {target_path}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # noqa: BLE001
        print(f"ERROR: {exc}", file=sys.stderr)
        raise
