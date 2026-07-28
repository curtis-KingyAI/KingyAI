#!/usr/bin/env python3
"""Build deterministic KAPI synthetic payloads without requiring local assets.

Portable generation uses a frozen manifest containing only the 12 pinned
construction chunks and ranks. Full proof against the hashed ``o200k_base``
asset is a separate, explicitly requested operation. Neither mode verifies a
model tokenizer mapping, provider request count, or billing equivalence.
"""

from __future__ import annotations

import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
from typing import Any, Mapping


REPO_ROOT = Path(__file__).resolve().parents[2]
CONSTRUCTION_MANIFEST_PATH = (
    REPO_ROOT / "kapi/fixtures/o200k-construction-manifest-v2.json"
)
O200K_ASSET_ENV = "KAPI_O200K_ASSET_PATH"
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

NONCLAIMS = [
    "not_verified_model_tokenizer_mapping",
    "not_provider_preflight_request_count_evidence",
    "not_billed_usage_count_evidence",
    "not_billing_equivalence",
]


def canonical_bytes(value: Any) -> bytes:
    return (
        json.dumps(value, ensure_ascii=False, separators=(",", ":"), sort_keys=True)
        + "\n"
    ).encode("utf-8")


def sha256_bytes(value: bytes) -> str:
    return hashlib.sha256(value).hexdigest()


def load_mergeable_ranks(asset_path: Path) -> dict[bytes, int]:
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


def build_construction_manifest(ranks: Mapping[bytes, int]) -> dict[str, Any]:
    entries: list[dict[str, Any]] = []
    for profile_id in sorted(CHUNKS):
        for direction in ("input", "output"):
            chunk = CHUNKS[profile_id][direction]
            chunk_bytes = chunk.encode("utf-8")
            token_rank = ranks.get(chunk_bytes)
            if token_rank is None:
                raise ValueError(f"chunk is not a single o200k_base token: {chunk!r}")
            entries.append(
                {
                    "chunk": chunk,
                    "chunk_bytes_base64": base64.b64encode(chunk_bytes).decode("ascii"),
                    "direction": direction,
                    "profile_id": profile_id,
                    "single_token_rank": token_rank,
                }
            )
    return {
        "derivation": {
            "algorithm": (
                "verify the complete source asset SHA-256, parse each non-empty "
                "line as base64 token bytes plus decimal rank, and select the exact "
                "UTF-8 bytes of the 12 pinned construction chunks"
            ),
            "full_source_asset_required_for_derivation_and_proof": True,
            "portable_payload_generation_requires_full_source_asset": False,
            "source_asset_sha256": O200K_ASSET_SHA256,
        },
        "entries": entries,
        "manifest_id": "kapi-o200k-construction-chunks",
        "manifest_version": "2",
        "nonclaims": NONCLAIMS,
        "reference_encoding": "o200k_base",
        "tiktoken_package_version": TIKTOKEN_VERSION,
    }


def derive_construction_manifest(asset_path: Path) -> dict[str, Any]:
    return build_construction_manifest(load_mergeable_ranks(asset_path))


def load_frozen_manifest(
    manifest_path: Path = CONSTRUCTION_MANIFEST_PATH,
) -> tuple[dict[str, Any], dict[tuple[str, str], int]]:
    raw = manifest_path.read_bytes()
    document = json.loads(raw)
    if raw != canonical_bytes(document):
        raise ValueError("construction manifest is not canonical JSON")
    if document.get("manifest_id") != "kapi-o200k-construction-chunks":
        raise ValueError("unexpected construction manifest id")
    if document.get("manifest_version") != "2":
        raise ValueError("unexpected construction manifest version")
    if document.get("reference_encoding") != "o200k_base":
        raise ValueError("unexpected construction manifest encoding")
    if document.get("tiktoken_package_version") != TIKTOKEN_VERSION:
        raise ValueError("unexpected construction manifest package version")
    if document.get("nonclaims") != NONCLAIMS:
        raise ValueError("construction manifest nonclaims changed")
    derivation = document.get("derivation")
    if not isinstance(derivation, Mapping):
        raise ValueError("construction manifest derivation is missing")
    if derivation.get("source_asset_sha256") != O200K_ASSET_SHA256:
        raise ValueError("construction manifest source asset hash changed")
    if derivation.get("portable_payload_generation_requires_full_source_asset") is not False:
        raise ValueError("portable generation must not require the full source asset")

    entries = document.get("entries")
    if not isinstance(entries, list) or len(entries) != 12:
        raise ValueError("construction manifest must contain exactly 12 entries")
    ranks: dict[tuple[str, str], int] = {}
    for entry in entries:
        if not isinstance(entry, Mapping):
            raise ValueError("construction manifest entry must be an object")
        profile_id = entry.get("profile_id")
        direction = entry.get("direction")
        if profile_id not in CHUNKS or direction not in {"input", "output"}:
            raise ValueError("construction manifest entry has an unknown key")
        key = (str(profile_id), str(direction))
        if key in ranks:
            raise ValueError(f"duplicate construction manifest entry: {key}")
        chunk = CHUNKS[str(profile_id)][str(direction)]
        if entry.get("chunk") != chunk:
            raise ValueError(f"construction manifest chunk mismatch: {key}")
        encoded = base64.b64encode(chunk.encode("utf-8")).decode("ascii")
        if entry.get("chunk_bytes_base64") != encoded:
            raise ValueError(f"construction manifest byte encoding mismatch: {key}")
        rank = entry.get("single_token_rank")
        if not isinstance(rank, int) or isinstance(rank, bool) or rank < 0:
            raise ValueError(f"invalid construction manifest rank: {key}")
        ranks[key] = rank
    expected_keys = {
        (profile_id, direction)
        for profile_id in CHUNKS
        for direction in ("input", "output")
    }
    if set(ranks) != expected_keys:
        raise ValueError("construction manifest entry set is incomplete")
    return document, ranks


def verify_source_asset(
    asset_path: Path,
    manifest_path: Path = CONSTRUCTION_MANIFEST_PATH,
) -> str:
    frozen, _ = load_frozen_manifest(manifest_path)
    derived = derive_construction_manifest(asset_path)
    if canonical_bytes(derived) != canonical_bytes(frozen):
        raise ValueError("frozen construction manifest does not match source asset")
    return sha256_bytes(canonical_bytes(frozen))


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
    ranks: Mapping[tuple[str, str], int],
) -> dict[str, Any]:
    size_factor, numerator, denominator = SIZE_FACTORS[size_code]
    target = scaled_target(
        HEADLINE_TARGETS[profile_id][direction], numerator, denominator
    )
    chunk = CHUNKS[profile_id][direction]
    token_rank = ranks[(profile_id, direction)]
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


def _process_all_payloads(*, write: bool) -> dict[str, dict[str, dict[str, str | int]]]:
    _, ranks = load_frozen_manifest()
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
                if write:
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


def build_all_payloads() -> dict[str, dict[str, dict[str, str | int]]]:
    return _process_all_payloads(write=False)


def write_all_payloads() -> dict[str, dict[str, dict[str, str | int]]]:
    return _process_all_payloads(write=True)


def _resolve_asset_path(argument: str | None) -> Path:
    value = argument or os.environ.get(O200K_ASSET_ENV)
    if not value:
        raise ValueError(
            f"full source-asset proof requires --asset-path or {O200K_ASSET_ENV}"
        )
    return Path(value).expanduser()


def _check_payloads() -> int:
    for by_direction in build_all_payloads().values():
        for by_size in by_direction.values():
            for row in by_size.values():
                path = REPO_ROOT / str(row["path"])
                if not path.exists() or sha256_bytes(path.read_bytes()) != row["sha256"]:
                    return 1
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Build or verify deterministic exact-count synthetic KAPI payloads."
    )
    modes = parser.add_mutually_exclusive_group()
    modes.add_argument(
        "--check",
        action="store_true",
        help="portable alias for --check-frozen-manifest",
    )
    modes.add_argument(
        "--check-frozen-manifest",
        action="store_true",
        help="verify payload bytes using only the repository-contained manifest",
    )
    modes.add_argument(
        "--verify-source-asset",
        action="store_true",
        help="prove the frozen manifest against the complete hashed source asset",
    )
    parser.add_argument(
        "--asset-path",
        help=f"full source asset path; alternatively set {O200K_ASSET_ENV}",
    )
    args = parser.parse_args()
    try:
        if args.verify_source_asset:
            digest = verify_source_asset(_resolve_asset_path(args.asset_path))
            print(
                json.dumps(
                    {
                        "construction_manifest_sha256": digest,
                        "source_asset_manifest_match": True,
                    }
                )
            )
            return 0
        if args.asset_path:
            parser.error("--asset-path is valid only with --verify-source-asset")
        if args.check or args.check_frozen_manifest:
            return _check_payloads()
        manifest = write_all_payloads()
        print(json.dumps(manifest, indent=2, sort_keys=True))
        return 0
    except (OSError, ValueError, json.JSONDecodeError) as exc:
        parser.error(str(exc))


if __name__ == "__main__":
    raise SystemExit(main())
