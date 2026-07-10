#!/usr/bin/env python3
"""Build deterministic KAPI synthetic payload files for methodology v0.2.0.

The generator intentionally does not vendor or import tiktoken package code.
It uses the previously acquired local o200k_base asset only to prove that each
repeated construction chunk is present as one mergeable token. The counted
scope is the JSON ``content`` field, not a provider request or billing record.
"""

from __future__ import annotations

import argparse
import base64
import hashlib
import json
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
AUTHORITATIVE_PACKAGE = Path(
    "/Users/curtispyke/Documents/Codex/2026-07-09/"
    "i-am-looking-to-do-this/outputs/kapi-tokenizer-verification-2026-07-10"
)
O200K_ASSET_PATH = AUTHORITATIVE_PACKAGE / "cache/o200k_base.tiktoken"
O200K_ASSET_SHA256 = (
    "446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d"
)
TIKTOKEN_VERSION = "0.13.0"

PROFILE_NAMES = {
    "analysis-reasoning": "Analysis/reasoning",
    "code-repair": "Code repair",
    "grounded-rag": "Grounded question answering/RAG",
    "structured-extraction": "Structured extraction/classification",
    "summarization-transformation": "Summarization/transformation",
    "tool-workflow": "Deterministic multi-step tool workflow",
}

HEADLINE_TARGETS = {
    "analysis-reasoning": {"input": 4000, "output": 500},
    "code-repair": {"input": 12000, "output": 3000},
    "grounded-rag": {"input": 10000, "output": 800},
    "structured-extraction": {"input": 2000, "output": 200},
    "summarization-transformation": {"input": 25000, "output": 1500},
    "tool-workflow": {"input": 30000, "output": 6000},
}

CHUNKS = {
    "analysis-reasoning": {"input": " alpha", "output": " result"},
    "code-repair": {"input": " code", "output": " patch"},
    "grounded-rag": {"input": " data", "output": " answer"},
    "structured-extraction": {"input": " field", "output": " value"},
    "summarization-transformation": {"input": " text", "output": " summary"},
    "tool-workflow": {"input": " step", "output": " trace"},
}

SIZE_FACTORS = {
    "075": ("0.75", 75, 100),
    "100": ("1.00", 100, 100),
    "125": ("1.25", 125, 100),
}


def canonical_bytes(value: Any) -> bytes:
    return (
        json.dumps(value, ensure_ascii=False, separators=(",", ":"), sort_keys=True)
        + "\n"
    ).encode("utf-8")


def sha256_bytes(value: bytes) -> str:
    return hashlib.sha256(value).hexdigest()


def load_mergeable_ranks(asset_path: Path = O200K_ASSET_PATH) -> dict[bytes, int]:
    data = asset_path.read_bytes()
    digest = sha256_bytes(data)
    if digest != O200K_ASSET_SHA256:
        raise ValueError(f"unexpected o200k_base asset hash: {digest}")
    ranks: dict[bytes, int] = {}
    for line in data.splitlines():
        if not line:
            continue
        token, rank = line.split()
        ranks[base64.b64decode(token)] = int(rank)
    return ranks


def scaled_target(headline_target: int, numerator: int, denominator: int) -> int:
    value = headline_target * numerator
    if value % denominator != 0:
        raise ValueError(
            f"target {headline_target} cannot scale exactly by {numerator}/{denominator}"
        )
    return value // denominator


def build_payload(
    *,
    profile_id: str,
    direction: str,
    size_code: str,
    ranks: dict[bytes, int],
) -> dict[str, Any]:
    size_factor, numerator, denominator = SIZE_FACTORS[size_code]
    target = scaled_target(
        HEADLINE_TARGETS[profile_id][direction], numerator, denominator
    )
    chunk = CHUNKS[profile_id][direction]
    token_rank = ranks.get(chunk.encode("utf-8"))
    if token_rank is None:
        raise ValueError(f"chunk is not a single o200k_base token: {chunk!r}")
    content = chunk * target
    return {
        "canonical_counted_content_field": "content",
        "construction_count_evidence_class": "construction_count",
        "construction_count_status": "exact_local_reference_count",
        "construction_count_tolerance_tokens": 0,
        "construction_token_count": target,
        "content": content,
        "dataset_kind": "synthetic",
        "direction": direction,
        "model_calls_performed": 0,
        "network_access_used": False,
        "o200k_base_asset_sha256": O200K_ASSET_SHA256,
        "o200k_base_count_verified": True,
        "profile_id": profile_id,
        "profile_name": PROFILE_NAMES[profile_id],
        "reference_token_design_target": target,
        "single_token_chunk": chunk,
        "single_token_rank": token_rank,
        "size_factor": size_factor,
        "tiktoken_package_version": TIKTOKEN_VERSION,
        "token_count_status": "exact_o200k_base_construction_count_not_billing",
    }


def build_all_payloads() -> dict[str, dict[str, dict[str, str | int]]]:
    ranks = load_mergeable_ranks()
    manifest: dict[str, dict[str, dict[str, str | int]]] = {}
    for profile_id in sorted(PROFILE_NAMES):
        manifest[profile_id] = {"input": {}, "output": {}}
        for direction in ("input", "output"):
            for size_code in ("075", "100", "125"):
                payload = build_payload(
                    profile_id=profile_id,
                    direction=direction,
                    size_code=size_code,
                    ranks=ranks,
                )
                path = REPO_ROOT / f"kapi/profiles/{profile_id}/{direction}-{size_code}.json"
                data = canonical_bytes(payload)
                manifest[profile_id][direction][size_code] = {
                    "path": str(path.relative_to(REPO_ROOT)),
                    "sha256": sha256_bytes(data),
                    "construction_token_count": payload["construction_token_count"],
                    "single_token_chunk": payload["single_token_chunk"],
                    "single_token_rank": payload["single_token_rank"],
                }
    return manifest


def write_all_payloads() -> dict[str, dict[str, dict[str, str | int]]]:
    ranks = load_mergeable_ranks()
    manifest: dict[str, dict[str, dict[str, str | int]]] = {}
    for profile_id in sorted(PROFILE_NAMES):
        manifest[profile_id] = {"input": {}, "output": {}}
        for direction in ("input", "output"):
            for size_code in ("075", "100", "125"):
                payload = build_payload(
                    profile_id=profile_id,
                    direction=direction,
                    size_code=size_code,
                    ranks=ranks,
                )
                path = REPO_ROOT / f"kapi/profiles/{profile_id}/{direction}-{size_code}.json"
                data = canonical_bytes(payload)
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_bytes(data)
                manifest[profile_id][direction][size_code] = {
                    "path": str(path.relative_to(REPO_ROOT)),
                    "sha256": sha256_bytes(data),
                    "construction_token_count": payload["construction_token_count"],
                    "single_token_chunk": payload["single_token_chunk"],
                    "single_token_rank": payload["single_token_rank"],
                }
    return manifest


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Build deterministic exact-count synthetic KAPI payloads."
    )
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    expected = build_all_payloads()
    if args.check:
        actual = write_all_payloads() if False else expected
        for profile_id, by_direction in actual.items():
            for direction, by_size in by_direction.items():
                for size_code, row in by_size.items():
                    path = REPO_ROOT / str(row["path"])
                    if not path.exists() or sha256_bytes(path.read_bytes()) != row["sha256"]:
                        return 1
        return 0
    manifest = write_all_payloads()
    print(json.dumps(manifest, indent=2, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
