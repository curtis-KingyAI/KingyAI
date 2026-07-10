"""Deterministic prototype release rendering and reproduction."""

from __future__ import annotations

import mimetypes
from pathlib import Path
from typing import Any, Mapping

from .calculation import calculate_index
from .util import (
PROTOTYPE_CITATION_TEXT,
    PROTOTYPE_NOTICE,
    atomic_write,
    canonical_json_bytes,
    csv_bytes,
    load_json,
    sha256_bytes,
    sha256_file,
)
from .validation import validate_or_raise


class ExportError(RuntimeError):
    """Raised when deterministic export or reproduction fails."""


REQUIRED_RELEASE_FILES = frozenset(
    {
        "inputs/dataset.json",
        "inputs/methodology.json",
        "calculation.json",
        "release.json",
        "latest.json",
        "history.csv",
        "components.csv",
    }
)


def _latest_week(calculation: Mapping[str, Any]) -> Mapping[str, Any]:
    weeks = calculation.get("weeks", [])
    if not isinstance(weeks, list) or not weeks:
        raise ExportError("calculation contains no weekly results")
    return weeks[-1]


def _compact(value: Any) -> str:
    return canonical_json_bytes(value).decode("utf-8").rstrip("\n")


def render_release_files(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
    *,
    validation_report: Mapping[str, Any] | None = None,
) -> dict[str, bytes]:
    """Render every release file except the manifest itself."""

    latest = dict(_latest_week(calculation))
    release_seed = (
        sha256_bytes(canonical_json_bytes(bundle))
        + sha256_bytes(canonical_json_bytes(methodology))
        + sha256_bytes(canonical_json_bytes(calculation))
    ).encode("ascii")
    release_id = f"kapi-prototype-{sha256_bytes(release_seed)[:16]}"
    cutoff = str(latest.get("cutoff_at", ""))

    frozen_bundle = canonical_json_bytes(bundle)
    frozen_methodology = canonical_json_bytes(methodology)
    calculation_bytes = canonical_json_bytes(calculation)

    release = {
        "release_id": release_id,
        "notice": PROTOTYPE_NOTICE,
        "not_for_publication": True,
        "deployed": False,
        "published": False,
        "observation_cutoff": cutoff,
        "methodology_id": calculation.get(
            "methodology_id", methodology.get("methodology_id")
        ),
        "methodology_version": calculation.get(
            "methodology_version", methodology.get("version")
        ),
        "dataset_id": calculation.get("dataset_id", bundle.get("dataset_id")),
        "evidence_mode": calculation.get("evidence_mode"),
        "capability_threshold": calculation.get("capability_threshold"),
        "base_period": calculation.get("base_period"),
        "latest": latest,
        "validation": validation_report or {},
        "citation": {
            "permitted": False,
            "text": PROTOTYPE_CITATION_TEXT,
        },
    }
    latest_document = {
        "release_id": release_id,
        "notice": PROTOTYPE_NOTICE,
        "not_for_publication": True,
        "latest": latest,
    }

    history_rows: list[dict[str, Any]] = []
    component_rows: list[dict[str, Any]] = []
    for week in calculation.get("weeks", []):
        concentration = week.get("concentration", {})
        profiles = week.get("profiles", [])
        concentration_status = concentration.get("status")
        history_rows.append(
            {
                "notice": PROTOTYPE_NOTICE,
                "dataset_kind": calculation.get("dataset_kind"),
                "not_for_publication": True,
                "citation_permitted": False,
                "week_id": week.get("week_id"),
                "cutoff_at": week.get("cutoff_at"),
                "calculation_status": week.get("calculation_status"),
                "release_status": week.get("release_status"),
                "basket_unit_cost_usd": week.get("basket_unit_cost"),
                "basket_60_cost_usd": week.get("basket_60_cost"),
                "index_level": week.get("index_level"),
                "geometric_index": week.get("geometric_index"),
                "frontier_index": week.get("frontier_index"),
                "mean_three_index": week.get("mean_three_index"),
                "week_over_week_percent": week.get("week_over_week_percent"),
                "four_week_percent": week.get("four_week_percent"),
                "since_base_percent": week.get("since_base_percent"),
                "year_over_year_percent": week.get("year_over_year_percent"),
                "coverage_profile_count": sum(
                    profile.get("calculation_status") == "complete"
                    for profile in profiles
                    if isinstance(profile, Mapping)
                ),
                "concentration_warning": concentration_status
                in {"warning", "withhold"},
                "concentration_withheld": concentration_status == "withhold",
            }
        )
        for profile in profiles:
            setter = profile.get("price_setter", {})
            if not isinstance(setter, Mapping) or not setter:
                setter = {
                    "endpoint_id": profile.get("price_setter_endpoint_id"),
                    "provider_id": profile.get("price_setter_provider_id"),
                    "creator_id": profile.get("price_setter_creator_id"),
                }
            selected_triple = profile.get(
                "selected_triple", profile.get("selected_triple_endpoint_ids", [])
            )
            component_rows.append(
                {
                    "notice": PROTOTYPE_NOTICE,
                    "dataset_kind": calculation.get("dataset_kind"),
                    "not_for_publication": True,
                    "citation_permitted": False,
                    "week_id": week.get("week_id"),
                    "cutoff_at": week.get("cutoff_at"),
                    "profile_id": profile.get("profile_id"),
                    "headline_price_usd": profile.get("headline_price"),
                    "frontier_price_usd": profile.get("frontier_price"),
                    "mean_three_price_usd": profile.get("mean_three_price"),
                    "weight": profile.get("weight"),
                    "contribution_percentage_points": profile.get(
                        "contribution_percentage_points"
                    ),
                    "price_setter_endpoint_id": setter.get("endpoint_id"),
                    "price_setter_provider_id": setter.get("provider_id"),
                    "price_setter_creator_id": setter.get("creator_id"),
                    "selected_triple": _compact(selected_triple),
                    "observation_ids": _compact(profile.get("observation_ids", [])),
                    "source_ids": _compact(profile.get("source_ids", [])),
                }
            )

    return {
        "inputs/dataset.json": frozen_bundle,
        "inputs/methodology.json": frozen_methodology,
        "calculation.json": calculation_bytes,
        "release.json": canonical_json_bytes(release),
        "latest.json": canonical_json_bytes(latest_document),
        "history.csv": csv_bytes(
            history_rows,
            [
                "notice",
                "dataset_kind",
                "not_for_publication",
                "citation_permitted",
                "week_id",
                "cutoff_at",
                "calculation_status",
                "release_status",
                "basket_unit_cost_usd",
                "basket_60_cost_usd",
                "index_level",
                "geometric_index",
                "frontier_index",
                "mean_three_index",
                "week_over_week_percent",
                "four_week_percent",
                "since_base_percent",
                "year_over_year_percent",
                "coverage_profile_count",
                "concentration_warning",
                "concentration_withheld",
            ],
        ),
        "components.csv": csv_bytes(
            component_rows,
            [
                "notice",
                "dataset_kind",
                "not_for_publication",
                "citation_permitted",
                "week_id",
                "cutoff_at",
                "profile_id",
                "headline_price_usd",
                "frontier_price_usd",
                "mean_three_price_usd",
                "weight",
                "contribution_percentage_points",
                "price_setter_endpoint_id",
                "price_setter_provider_id",
                "price_setter_creator_id",
                "selected_triple",
                "observation_ids",
                "source_ids",
            ],
        ),
    }


def _implementation_hashes(repository_root: Path) -> list[dict[str, Any]]:
    paths: list[Path] = []
    for pattern in ("kapi/*.py", "kapi/schema/*.sql"):
        paths.extend(repository_root.glob(pattern))
    return [
        {
            "path": path.relative_to(repository_root).as_posix(),
            "sha256": sha256_file(path),
            "size_bytes": path.stat().st_size,
        }
        for path in sorted(set(paths))
        if path.is_file()
    ]


def build_manifest(
    files: Mapping[str, bytes],
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
    *,
    repository_root: str | Path,
) -> dict[str, Any]:
    latest = _latest_week(calculation)
    release = load_json_bytes(files["release.json"])
    source_ids = sorted(
        {
            source_id
            for week in calculation.get("weeks", [])
            for profile in week.get("profiles", [])
            for source_id in profile.get("source_ids", [])
        }
    )
    source_index = {
        source.get("id"): source
        for source in bundle.get("source_artifacts", [])
        if isinstance(source, Mapping)
    }
    source_lineage = [
        {
            "source_id": source_id,
            "url": source_index.get(source_id, {}).get("url"),
            "content_sha256": source_index.get(source_id, {}).get("content_sha256"),
            "evidence_grade": source_index.get(source_id, {}).get("evidence_grade"),
            "retrieved_at": source_index.get(source_id, {}).get("retrieved_at"),
        }
        for source_id in source_ids
    ]
    file_entries = []
    for relative, data in sorted(files.items()):
        media_type = mimetypes.guess_type(relative)[0] or "application/octet-stream"
        file_entries.append(
            {
                "path": relative,
                "sha256": sha256_bytes(data),
                "size_bytes": len(data),
                "media_type": media_type,
            }
        )
    return {
        "manifest_version": "0.1.0",
        "release_id": release["release_id"],
        "notice": PROTOTYPE_NOTICE,
        "not_for_publication": True,
        "created_at": latest.get("cutoff_at"),
        "dataset_id": bundle.get("dataset_id"),
        "dataset_sha256": sha256_bytes(canonical_json_bytes(bundle)),
        "methodology_id": methodology.get("methodology_id"),
        "methodology_version": methodology.get("version"),
        "methodology_sha256": sha256_bytes(canonical_json_bytes(methodology)),
        "calculation_sha256": sha256_bytes(canonical_json_bytes(calculation)),
        "files": file_entries,
        "source_lineage": source_lineage,
        "implementation": _implementation_hashes(Path(repository_root).resolve()),
        "spending": {
            "external_spend_usd": "0",
            "paid_api_calls": 0,
            "subscriptions_started": 0,
        },
    }


def export_release(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
    output_dir: str | Path,
    *,
    repository_root: str | Path,
    validation_report: Mapping[str, Any] | None = None,
) -> dict[str, Any]:
    destination = Path(output_dir)
    destination.mkdir(parents=True, exist_ok=True)
    files = render_release_files(
        bundle,
        methodology,
        calculation,
        validation_report=validation_report,
    )
    manifest = build_manifest(
        files,
        bundle,
        methodology,
        calculation,
        repository_root=repository_root,
    )
    for relative, data in files.items():
        path = destination / relative
        path.parent.mkdir(parents=True, exist_ok=True)
        atomic_write(path, data)
    manifest_bytes = canonical_json_bytes(manifest)
    atomic_write(destination / "provenance-manifest.json", manifest_bytes)
    return {
        "output_dir": str(destination.resolve()),
        "release_id": manifest["release_id"],
        "manifest_sha256": sha256_bytes(manifest_bytes),
        "files_written": len(files) + 1,
        "not_for_publication": True,
    }


def reproduce_release(
    release_dir: str | Path,
    *,
    repository_root: str | Path,
) -> dict[str, Any]:
    directory = Path(release_dir).resolve()
    manifest_path = directory / "provenance-manifest.json"
    if not manifest_path.is_file():
        raise ExportError("provenance-manifest.json is missing")
    manifest = load_json(manifest_path)
    mismatches: list[dict[str, Any]] = []
    manifest_files = manifest.get("files", [])
    if not isinstance(manifest_files, list):
        return {
            "reproduced": False,
            "release_id": manifest.get("release_id"),
            "mismatches": [
                {"path": "provenance-manifest.json", "problem": "invalid_file_inventory"}
            ],
        }
    declared_paths: set[str] = set()
    for entry in manifest_files:
        if not isinstance(entry, Mapping) or not isinstance(entry.get("path"), str):
            mismatches.append(
                {"path": "provenance-manifest.json", "problem": "invalid_file_entry"}
            )
            continue
        relative = entry["path"]
        path = (directory / relative).resolve()
        try:
            path.relative_to(directory)
        except ValueError:
            mismatches.append({"path": relative, "problem": "unsafe_file_path"})
            continue
        declared_paths.add(relative)
        if not path.is_file():
            mismatches.append({"path": relative, "problem": "missing"})
            continue
        actual = sha256_file(path)
        if actual != entry.get("sha256"):
            mismatches.append(
                {
                    "path": relative,
                    "problem": "hash_mismatch",
                    "expected": entry.get("sha256"),
                    "actual": actual,
                }
            )
    for relative in sorted(REQUIRED_RELEASE_FILES - declared_paths):
        mismatches.append(
            {"path": relative, "problem": "required_manifest_entry_missing"}
        )
    for relative in sorted(declared_paths - REQUIRED_RELEASE_FILES):
        mismatches.append(
            {"path": relative, "problem": "unexpected_manifest_entry"}
        )
    expected_inventory = declared_paths | {"provenance-manifest.json"}
    actual_inventory = {
        path.relative_to(directory).as_posix()
        for path in directory.rglob("*")
        if path.is_file()
    }
    for relative in sorted(actual_inventory - expected_inventory):
        mismatches.append({"path": relative, "problem": "unmanifested_file"})

    repository = Path(repository_root).resolve()
    implementation_entries = manifest.get("implementation", [])
    if not isinstance(implementation_entries, list):
        mismatches.append(
            {
                "path": "provenance-manifest.json",
                "problem": "invalid_implementation_inventory",
            }
        )
        implementation_entries = []
    for entry in implementation_entries:
        if not isinstance(entry, Mapping) or not isinstance(entry.get("path"), str):
            mismatches.append(
                {
                    "path": "provenance-manifest.json",
                    "problem": "invalid_implementation_entry",
                }
            )
            continue
        relative = entry["path"]
        path = (repository / relative).resolve()
        try:
            path.relative_to(repository)
        except ValueError:
            mismatches.append({"path": relative, "problem": "unsafe_code_path"})
            continue
        if not path.is_file():
            mismatches.append({"path": relative, "problem": "code_missing"})
            continue
        actual = sha256_file(path)
        if actual != entry.get("sha256"):
            mismatches.append(
                {
                    "path": relative,
                    "problem": "code_hash_mismatch",
                    "expected": entry.get("sha256"),
                    "actual": actual,
                }
            )
    if mismatches:
        return {
            "reproduced": False,
            "release_id": manifest.get("release_id"),
            "mismatches": mismatches,
        }

    bundle = load_json(directory / "inputs/dataset.json")
    methodology = load_json(directory / "inputs/methodology.json")
    recorded_calculation = load_json(directory / "calculation.json")
    validation = validate_or_raise(
        bundle, methodology, repository_root=repository_root
    )
    calculation = calculate_index(
        bundle,
        methodology,
        evidence_mode=recorded_calculation.get("evidence_mode", "official"),
        capability_threshold=recorded_calculation.get("capability_threshold"),
        weights=recorded_calculation.get("weights"),
    )
    expected_files = render_release_files(
        bundle,
        methodology,
        calculation,
        validation_report=validation,
    )
    for relative, expected in expected_files.items():
        actual_path = directory / relative
        if not actual_path.is_file() or actual_path.read_bytes() != expected:
            mismatches.append(
                {"path": relative, "problem": "recalculated_bytes_mismatch"}
            )
    expected_manifest = build_manifest(
        expected_files,
        bundle,
        methodology,
        calculation,
        repository_root=repository_root,
    )
    expected_manifest_bytes = canonical_json_bytes(expected_manifest)
    actual_manifest_bytes = manifest_path.read_bytes()
    if actual_manifest_bytes != expected_manifest_bytes:
        mismatches.append(
            {
                "path": "provenance-manifest.json",
                "problem": "recalculated_manifest_mismatch",
                "expected": sha256_bytes(expected_manifest_bytes),
                "actual": sha256_bytes(actual_manifest_bytes),
            }
        )
    return {
        "reproduced": not mismatches,
        "release_id": manifest.get("release_id"),
        "checked_files": len(manifest.get("files", [])),
        "mismatches": mismatches,
        "calculation_sha256": sha256_bytes(canonical_json_bytes(calculation)),
    }


def load_json_bytes(value: bytes) -> Any:
    import json

    return json.loads(value.decode("utf-8"))
