"""Build the forward-only v0.3 public input from immutable synthetic evidence."""

from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[2]
HISTORICAL_FIXTURE = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
METHODOLOGY = ROOT / "kapi/config/methodology-v0.3.0.json"
OUTPUT = ROOT / "kapi/fixtures/synthetic-forward-governance-v0.3.0.json"


def _load(path: Path) -> dict[str, Any]:
    value = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(value, dict):
        raise ValueError(f"{path} must contain a JSON object")
    return value


def _canonical_bytes(value: Any) -> bytes:
    return (
        json.dumps(value, sort_keys=True, separators=(",", ":"), ensure_ascii=False)
        + "\n"
    ).encode("utf-8")


def build_forward_bundle() -> dict[str, Any]:
    bundle = _load(HISTORICAL_FIXTURE)
    methodology_bytes = METHODOLOGY.read_bytes()
    methodology = json.loads(methodology_bytes)

    # Test-oracle values and self-reported execution/spend metadata are not
    # mathematical inputs and cannot enter a public provenance bundle.
    bundle.pop("expected_result", None)
    bundle.pop("generation", None)
    bundle.pop("base_period_week_ids", None)
    bundle.pop("current_week_id", None)
    for observation in bundle["price_observations"]:
        observation.setdefault("supersedes_observation_id", None)
    for correction in bundle["corrections"]:
        correction.setdefault("supersedes_correction_id", None)
    bundle["schema_version"] = "kapi-bundle-v0.3.0"
    bundle["dataset_id"] = "synthetic-forward-governance-v0.3.0"
    bundle["methodology"] = {
        "config_path": "kapi/config/methodology-v0.3.0.json",
        "config_sha256": hashlib.sha256(methodology_bytes).hexdigest(),
        "id": methodology["methodology_id"],
        "version": methodology["version"],
    }
    return bundle


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    expected = _canonical_bytes(build_forward_bundle())
    if args.check:
        if not OUTPUT.is_file() or OUTPUT.read_bytes() != expected:
            raise SystemExit("forward v0.3 bundle is not reproducible")
        return 0
    OUTPUT.write_bytes(expected)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
