from __future__ import annotations

import copy
import csv
import io
import json
import tempfile
import unittest
from pathlib import Path

from kapi.calculation import calculate_index
from kapi.exporter import export_release, render_release_files, reproduce_release
from kapi.util import canonical_json_bytes, sha256_file
from kapi.validation import validate_or_raise


ROOT = Path(__file__).resolve().parents[2]
BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.1.0.json"


class ExporterTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = json.loads(BUNDLE_PATH.read_text(encoding="utf-8"))
        cls.methodology = json.loads(METHOD_PATH.read_text(encoding="utf-8"))
        cls.validation = validate_or_raise(
            cls.bundle, cls.methodology, repository_root=ROOT
        )
        cls.calculation = calculate_index(cls.bundle, cls.methodology)

    def test_export_contains_frozen_inputs_csv_json_and_manifest(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            expected = {
                "inputs/dataset.json",
                "inputs/methodology.json",
                "calculation.json",
                "release.json",
                "latest.json",
                "history.csv",
                "components.csv",
                "provenance-manifest.json",
            }
            actual = {
                path.relative_to(directory).as_posix()
                for path in Path(directory).rglob("*")
                if path.is_file()
            }
            self.assertEqual(expected, actual)
            self.assertEqual(8, summary["files_written"])

            release = json.loads(
                (Path(directory) / "release.json").read_text(encoding="utf-8")
            )
            self.assertTrue(release["not_for_publication"])
            self.assertFalse(release["deployed"])
            self.assertFalse(release["published"])
            self.assertEqual(
                "withheld_concentration", release["latest"]["release_status"]
            )
            self.assertEqual("53.836833602584814216478190630048465266558966074314",
                             release["latest"]["index_level"])

            calculation = json.loads(
                (Path(directory) / "calculation.json").read_text(encoding="utf-8")
            )
            self.assertTrue(calculation["not_for_publication"])
            self.assertEqual(
                "synthetic_official_policy_simulation", calculation["series_type"]
            )
            for filename in ("history.csv", "components.csv"):
                with (Path(directory) / filename).open(
                    newline="", encoding="utf-8"
                ) as handle:
                    rows = list(csv.DictReader(handle))
                self.assertTrue(rows)
                self.assertTrue(
                    all(row["not_for_publication"] == "True" for row in rows)
                )
                self.assertTrue(
                    all(row["citation_permitted"] == "False" for row in rows)
                )
                self.assertTrue(
                    all(row["dataset_kind"] == "synthetic" for row in rows)
                )
                self.assertTrue(
                    all("SYNTHETIC KAPI PROTOTYPE" in row["notice"] for row in rows)
                )

    def test_reproduce_is_byte_exact_and_detects_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertTrue(report["reproduced"])
            self.assertEqual([], report["mismatches"])

            history = Path(directory) / "history.csv"
            history.write_bytes(history.read_bytes() + b"tampered\n")
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                "history.csv",
                {item["path"] for item in report["mismatches"]},
            )

    def test_two_exports_are_identical(self) -> None:
        with tempfile.TemporaryDirectory() as first, tempfile.TemporaryDirectory() as second:
            first_summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                first,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            second_summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                second,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            self.assertEqual(first_summary["release_id"], second_summary["release_id"])
            for first_path in sorted(Path(first).rglob("*")):
                if not first_path.is_file():
                    continue
                relative = first_path.relative_to(first)
                second_path = Path(second) / relative
                self.assertTrue(second_path.is_file(), relative.as_posix())
                self.assertEqual(
                    sha256_file(first_path),
                    sha256_file(second_path),
                    relative.as_posix(),
                )

    def test_manifest_metadata_tampering_is_detected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            manifest_path = Path(directory) / "provenance-manifest.json"
            manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
            manifest["notice"] = "tampered"
            manifest["spending"]["external_spend_usd"] = "999"
            manifest["source_lineage"] = []
            manifest["implementation"] = []
            manifest_path.write_bytes(canonical_json_bytes(manifest))

            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                "provenance-manifest.json",
                {item["path"] for item in report["mismatches"]},
            )

    def test_nondefault_weight_export_reproduces(self) -> None:
        weights = {
            "analysis-reasoning": "20",
            "code-repair": "15",
            "grounded-rag": "20",
            "structured-extraction": "20",
            "summarization-transformation": "15",
            "tool-workflow": "10",
        }
        calculation = calculate_index(
            self.bundle, self.methodology, weights=weights
        )
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertTrue(report["reproduced"], report["mismatches"])

    def test_incomplete_week_csv_coverage_counts_complete_profiles_only(self) -> None:
        calculation = copy.deepcopy(self.calculation)
        for profile in calculation["weeks"][0]["profiles"]:
            profile["calculation_status"] = "incomplete"
        files = render_release_files(
            self.bundle,
            self.methodology,
            calculation,
            validation_report=self.validation,
        )
        rows = list(
            csv.DictReader(io.StringIO(files["history.csv"].decode("utf-8")))
        )
        self.assertEqual("0", rows[0]["coverage_profile_count"])

    def test_unmanifested_release_file_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            (Path(directory) / "unmanifested.txt").write_text(
                "not part of the release", encoding="utf-8"
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                {"path": "unmanifested.txt", "problem": "unmanifested_file"},
                report["mismatches"],
            )

    def test_required_file_removed_from_manifest_and_disk_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            dataset_path = Path(directory) / "inputs/dataset.json"
            dataset_path.unlink()
            manifest_path = Path(directory) / "provenance-manifest.json"
            manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
            manifest["files"] = [
                entry
                for entry in manifest["files"]
                if entry["path"] != "inputs/dataset.json"
            ]
            manifest_path.write_bytes(canonical_json_bytes(manifest))

            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                {
                    "path": "inputs/dataset.json",
                    "problem": "required_manifest_entry_missing",
                },
                report["mismatches"],
            )


if __name__ == "__main__":
    unittest.main()
