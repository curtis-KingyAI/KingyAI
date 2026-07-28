"""Deterministic prototype release rendering and reproduction."""

from __future__ import annotations

import mimetypes
from pathlib import Path
from typing import Any, Mapping

from .calculation import CalculationError, calculate_index
from .governance import (
    CURRENT_OPERATOR_REVIEW_LABEL,
    CURRENT_UNREVIEWED_LABEL,
    EXTERNAL_RELEASE_REVIEW_LABEL,
    METHODOLOGY_REVIEWED_OPERATOR_LABEL,
)
from .util import (
    artifact_notice,
    atomic_write,
    canonical_json_bytes,
    csv_bytes,
    load_json,
    sha256_bytes,
    sha256_file,
)
from .validation import find_input_claim_paths, validate_or_raise


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
_NON_CONTENT_FIELDS = frozenset(
    {
        "citation",
        "deployed",
        "governance_attribution",
        "governance_state",
        "not_for_publication",
        "publication_eligible",
        "publication_state",
        "published",
        "review_label",
    }
)
_GOVERNANCE_FIELDS = frozenset(
    {
        "governance_state",
        "review_label",
        "publication_state",
        "publication_eligible",
    }
)
_TOP_LEVEL_ONLY_GOVERNANCE_FIELDS = frozenset(
    {
        "governance_attribution",
        "not_for_publication",
        "published",
        "deployed",
    }
)


def _validate_nested_governance(value: Any, *, path: str = "calculation") -> None:
    """Reject any nested object that could contradict the public envelope.

    Release JSON includes the complete calculation, and the latest week is also
    copied into two public-facing documents.  Therefore validating only the
    calculation's root would permit a forged week or sensitivity result to
    publish a stronger governance claim.  Every object carrying even one
    governed field must carry the complete, exact unreviewed tuple.
    """

    if isinstance(value, Mapping):
        present = _GOVERNANCE_FIELDS.intersection(value)
        if present:
            missing = _GOVERNANCE_FIELDS.difference(value)
            if missing:
                raise ExportError(
                    f"{path} has an incomplete governance envelope: "
                    f"missing {', '.join(sorted(missing))}"
                )
            if value.get("governance_state") != "unreviewed":
                raise ExportError(f"{path}.governance_state must be unreviewed")
            if value.get("review_label") != CURRENT_UNREVIEWED_LABEL:
                raise ExportError(
                    f"{path}.review_label must be the current unreviewed label"
                )
            if value.get("publication_state") != "not_authorized":
                raise ExportError(f"{path}.publication_state must be not_authorized")
            if value.get("publication_eligible") is not False:
                raise ExportError(f"{path}.publication_eligible must be false")

        if path != "calculation":
            unexpected = _TOP_LEVEL_ONLY_GOVERNANCE_FIELDS.intersection(value)
            if unexpected:
                raise ExportError(
                    f"{path} contains top-level-only governance fields: "
                    f"{', '.join(sorted(unexpected))}"
                )

        for key, item in value.items():
            _validate_nested_governance(item, path=f"{path}.{key}")
    elif isinstance(value, list):
        for position, item in enumerate(value):
            _validate_nested_governance(item, path=f"{path}[{position}]")


def _validate_governance_export(calculation: Mapping[str, Any]) -> None:
    label = calculation.get("review_label")
    attribution = calculation.get("governance_attribution")
    if label == CURRENT_UNREVIEWED_LABEL:
        if calculation.get("governance_state") != "unreviewed":
            raise ExportError("current unreviewed label/state mismatch")
        if calculation.get("publication_state") != "not_authorized" or bool(
            calculation.get("publication_eligible")
        ):
            raise ExportError(
                "current governance vintage cannot export publication readiness"
            )
        if attribution not in (None, {}):
            raise ExportError("an unreviewed draft cannot carry review attribution")
        if calculation.get("not_for_publication") is not True:
            raise ExportError("current governance vintage must be not for publication")
        if (
            calculation.get("published") is not False
            or calculation.get("deployed") is not False
        ):
            raise ExportError(
                "current governance vintage cannot be published or deployed"
            )
        _validate_nested_governance(calculation)
        return
    if label == CURRENT_OPERATOR_REVIEW_LABEL:
        raise ExportError(
            "policy v1.0.0 has no trusted operator identity adapter; "
            "operator-review claims cannot be exported"
        )
    if label == METHODOLOGY_REVIEWED_OPERATOR_LABEL:
        raise ExportError(
            "policy v1.0.0 unconditionally rejects external-methodology claims; "
            "a new reviewed policy vintage and trusted verifier adapter are required"
        )
    if label == EXTERNAL_RELEASE_REVIEW_LABEL:
        raise ExportError(
            "policy v1.0.0 unconditionally rejects external-release claims; "
            "a new reviewed policy vintage and trusted verifier adapter are required"
        )
    raise ExportError("review_label is not a governed public label")


def _latest_week(calculation: Mapping[str, Any]) -> Mapping[str, Any]:
    weeks = calculation.get("weeks", [])
    if not isinstance(weeks, list) or not weeks:
        raise ExportError("calculation contains no weekly results")
    return weeks[-1]


def _validate_deterministic_calculation(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
) -> None:
    """Require the complete calculation to be derived from the frozen inputs.

    A field-name denylist is not an authorization boundary: a caller could add
    a synonymous review/approval field that the renderer would copy into
    calculation.json or the latest-week documents. Exact recomputation equality
    rejects every caller-added, removed, or altered field, not only known
    governance names.
    """

    try:
        expected = calculate_index(
            bundle,
            methodology,
            evidence_mode=calculation.get("evidence_mode", "official"),
            capability_threshold=calculation.get("capability_threshold"),
            weights=calculation.get("weights"),
        )
    except CalculationError as error:
        raise ExportError(
            f"calculation cannot be reproduced from the frozen inputs: {error}"
        ) from error
    if canonical_json_bytes(calculation) != canonical_json_bytes(expected):
        raise ExportError(
            "caller-supplied calculation does not exactly match deterministic "
            "recomputation from the frozen inputs"
        )


def _compact(value: Any) -> str:
    return canonical_json_bytes(value).decode("utf-8").rstrip("\n")


def _coverage_profile_count(profiles: Any) -> int:
    """Count only complete profile mappings for a rendered history row."""

    if not isinstance(profiles, list):
        return 0
    return sum(
        profile.get("calculation_status") == "complete"
        for profile in profiles
        if isinstance(profile, Mapping)
    )


def _mathematical_content(value: Any) -> Any:
    """Remove mutable governance/presentation fields from content identity."""

    if isinstance(value, Mapping):
        return {
            key: _mathematical_content(item)
            for key, item in value.items()
            if key not in _NON_CONTENT_FIELDS
        }
    if isinstance(value, list):
        return [_mathematical_content(item) for item in value]
    return value


def derive_release_id(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
) -> str:
    """Derive a stable ID from immutable inputs and mathematical output only."""

    identity = {
        "dataset_sha256": sha256_bytes(canonical_json_bytes(bundle)),
        "methodology_sha256": sha256_bytes(canonical_json_bytes(methodology)),
        "mathematical_output_sha256": sha256_bytes(
            canonical_json_bytes(_mathematical_content(calculation))
        ),
    }
    return f"kapi-content-{sha256_bytes(canonical_json_bytes(identity))[:16]}"


def render_release_files(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    calculation: Mapping[str, Any],
    *,
    validation_report: Mapping[str, Any] | None = None,
    repository_root: str | Path | None = None,
) -> dict[str, bytes]:
    """Render every release file except the manifest itself."""

    input_claim_paths = find_input_claim_paths(bundle)
    if input_claim_paths:
        raise ExportError(
            "frozen input bundle contains a claim-bearing key or assertion-like "
            f"string at {', '.join(input_claim_paths)}"
        )
    _validate_governance_export(calculation)
    _validate_deterministic_calculation(bundle, methodology, calculation)
    root = (
        Path(repository_root).resolve()
        if repository_root is not None
        else Path(__file__).resolve().parents[1]
    )
    authoritative_validation = validate_or_raise(
        bundle, methodology, repository_root=root
    )
    if validation_report is not None and canonical_json_bytes(
        validation_report
    ) != canonical_json_bytes(authoritative_validation):
        raise ExportError(
            "caller-supplied validation report does not exactly match "
            "validation recomputed from the frozen inputs"
        )

    latest = dict(_latest_week(calculation))
    notice, citation_text = artifact_notice(calculation.get("dataset_kind"))
    release_id = derive_release_id(bundle, methodology, calculation)
    cutoff = str(latest.get("cutoff_at", ""))

    frozen_bundle = canonical_json_bytes(bundle)
    frozen_methodology = canonical_json_bytes(methodology)
    calculation_bytes = canonical_json_bytes(calculation)

    release = {
        "release_id": release_id,
        "notice": notice,
        "not_for_publication": True,
        "deployed": False,
        "published": False,
        "calculation_disposition": calculation.get("calculation_disposition"),
        "governance_state": calculation.get("governance_state"),
        "review_label": calculation.get("review_label"),
        "publication_state": calculation.get("publication_state"),
        "publication_eligible": False,
        "governance_attribution": calculation.get("governance_attribution"),
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
        "validation": authoritative_validation,
        "citation": {
            "permitted": False,
            "text": citation_text,
        },
    }
    latest_document = {
        "release_id": release_id,
        "notice": notice,
        "not_for_publication": True,
        "calculation_disposition": calculation.get("calculation_disposition"),
        "governance_state": calculation.get("governance_state"),
        "review_label": calculation.get("review_label"),
        "publication_state": calculation.get("publication_state"),
        "publication_eligible": False,
        "governance_attribution": calculation.get("governance_attribution"),
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
                "notice": notice,
                "dataset_kind": calculation.get("dataset_kind"),
                "not_for_publication": True,
                "citation_permitted": False,
                "week_id": week.get("week_id"),
                "cutoff_at": week.get("cutoff_at"),
                "calculation_status": week.get("calculation_status"),
                "release_status": week.get("release_status"),
                "calculation_disposition": week.get("calculation_disposition"),
                "governance_state": week.get("governance_state"),
                "review_label": week.get("review_label"),
                "publication_state": week.get("publication_state"),
                "publication_eligible": False,
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
                "coverage_profile_count": _coverage_profile_count(profiles),
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
                    "notice": notice,
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
                "calculation_disposition",
                "governance_state",
                "review_label",
                "publication_state",
                "publication_eligible",
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


def _implementation_hashes(
    repository_root: Path, *, methodology_version: str
) -> list[dict[str, Any]]:
    paths: list[Path] = []
    for pattern in ("kapi/*.py", "kapi/schema/*.sql"):
        paths.extend(repository_root.glob(pattern))
    # v0.2.x manifests retain their exact historical implementation inventory.
    # The forward v0.3.0 path inventories only the active secondary-check module,
    # not the hash-pinned legacy module retained solely for old vintages.
    if methodology_version == "0.3.0":
        paths = [path for path in paths if path.name != "independent.py"]
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
    authoritative_files = render_release_files(
        bundle,
        methodology,
        calculation,
        repository_root=repository_root,
    )
    if set(files) != set(authoritative_files):
        raise ExportError(
            "manifest input files must be the exact required release-file set"
        )
    for relative, expected in authoritative_files.items():
        if files.get(relative) != expected:
            raise ExportError(
                f"manifest input {relative} does not match the authoritative render"
            )
    latest = _latest_week(calculation)
    notice, _ = artifact_notice(calculation.get("dataset_kind"))
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
        "notice": notice,
        "not_for_publication": True,
        "calculation_disposition": calculation.get("calculation_disposition"),
        "governance_state": calculation.get("governance_state"),
        "review_label": calculation.get("review_label"),
        "publication_state": calculation.get("publication_state"),
        "publication_eligible": False,
        "governance_attribution": calculation.get("governance_attribution"),
        "created_at": latest.get("cutoff_at"),
        "dataset_id": bundle.get("dataset_id"),
        "dataset_sha256": sha256_bytes(canonical_json_bytes(bundle)),
        "methodology_id": methodology.get("methodology_id"),
        "methodology_version": methodology.get("version"),
        "methodology_sha256": sha256_bytes(canonical_json_bytes(methodology)),
        "calculation_sha256": sha256_bytes(canonical_json_bytes(calculation)),
        "files": file_entries,
        "source_lineage": source_lineage,
        "implementation": _implementation_hashes(
            Path(repository_root).resolve(),
            methodology_version=str(methodology.get("version", "")),
        ),
        "spending": {
            "status": "not_measured_not_evidenced",
            "scope": "artifact_generation_spend_and_provider_activity_not_bound",
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
    files = render_release_files(
        bundle,
        methodology,
        calculation,
        validation_report=validation_report,
        repository_root=repository_root,
    )
    manifest = build_manifest(
        files,
        bundle,
        methodology,
        calculation,
        repository_root=repository_root,
    )
    destination.mkdir(parents=True, exist_ok=True)
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
                {
                    "path": "provenance-manifest.json",
                    "problem": "invalid_file_inventory",
                }
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
        mismatches.append({"path": relative, "problem": "unexpected_manifest_entry"})
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
    validation = validate_or_raise(bundle, methodology, repository_root=repository_root)
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
        repository_root=repository_root,
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
