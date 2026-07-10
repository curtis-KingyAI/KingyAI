from __future__ import annotations

import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
BUNDLE = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
METHOD = ROOT / "kapi/config/methodology-v0.2.2.json"


def run_cli(*arguments: str) -> dict:
    completed = subprocess.run(
        [sys.executable, "-m", "kapi", *arguments],
        cwd=ROOT,
        check=True,
        capture_output=True,
        text=True,
    )
    return json.loads(completed.stdout)


class CliTests(unittest.TestCase):
    def test_validate_calculate_export_and_reproduce(self) -> None:
        validation = run_cli(
            "validate",
            "--bundle",
            str(BUNDLE),
            "--method",
            str(METHOD),
        )
        self.assertTrue(validation["valid"])

        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            calculation_path = root / "calculation.json"
            calculate = run_cli(
                "calculate",
                "--bundle",
                str(BUNDLE),
                "--method",
                str(METHOD),
                "--output",
                str(calculation_path),
            )
            self.assertEqual(
                "withheld_concentration", calculate["calculation_status"]
            )
            self.assertEqual("withheld_concentration",
                             calculate["latest"]["release_status"])

            release_dir = root / "release"
            exported = run_cli(
                "export",
                "--bundle",
                str(BUNDLE),
                "--method",
                str(METHOD),
                "--output-dir",
                str(release_dir),
            )
            self.assertTrue(exported["not_for_publication"])
            reproduced = run_cli(
                "reproduce",
                "--release-dir",
                str(release_dir),
            )
            self.assertTrue(reproduced["reproduced"])

    def test_append_only_database_cli_roundtrip(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            database = root / "kapi.sqlite3"
            dumped = root / "dump.json"
            initialized = run_cli(
                "init-db", "--database", str(database)
            )
            self.assertTrue(initialized["append_only"])
            ingested = run_cli(
                "ingest",
                "--database",
                str(database),
                "--bundle",
                str(BUNDLE),
                "--method",
                str(METHOD),
            )
            self.assertEqual(112, ingested["summary"]["price_observations"])
            run_cli(
                "dump",
                "--database",
                str(database),
                "--output",
                str(dumped),
            )
            self.assertEqual(
                json.loads(BUNDLE.read_text(encoding="utf-8")),
                json.loads(dumped.read_text(encoding="utf-8")),
            )


if __name__ == "__main__":
    unittest.main()
