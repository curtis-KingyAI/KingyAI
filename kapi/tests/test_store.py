"""Tests for the append-only KAPI SQLite store."""

from __future__ import annotations

import copy
import sqlite3
import unittest

from kapi.store import (
    APPEND_ONLY_TABLES,
    StoreError,
    dump_bundle,
    ingest_bundle,
    init_database,
)


HASH_A = "a17fcf0a2f50e2d495e4f90ce263410edc183add6c62699a2facbccf60410f74"
HASH_B = "b" * 64


def sample_bundle() -> dict:
    return {
        "schema_version": "kapi-bundle-v0.1.0",
        "dataset_id": "synthetic-store-test-v1",
        "dataset_kind": "synthetic",
        "weeks": [
            {"id": "week-2026-07-03", "cutoff_at": "2026-07-03T16:00:00Z"}
        ],
        "providers": [
            {"id": "provider-a", "name": "Provider A", "synthetic": True}
        ],
        "creators": [
            {"id": "creator-a", "name": "Creator A", "synthetic": True}
        ],
        "models": [
            {
                "id": "model-a-v1",
                "creator_id": "creator-a",
                "version": "model-a-2026-06-01",
                "alias_type": "immutable",
                "immutable_version": True,
            }
        ],
        "endpoints": [
            {
                "id": "endpoint-a-v1",
                "provider_id": "provider-a",
                "model_id": "model-a-v1",
                "configuration_id": "config-a-reasoning-disabled",
                "region": "US",
                "tier": "standard",
                "public": True,
                "ga": True,
                "synchronous": True,
                "on_demand": True,
                "available_us": True,
                "standard_list_price": True,
                "reasoning_mode": "disabled",
                "billing_tokenizer": "synthetic-counts-v1",
                "tokenizer_reproducible": True,
                "features": ["structured_output", "text_input", "text_output"],
                "available_from": "2026-06-01T00:00:00Z",
                "available_until": None,
            }
        ],
        "source_artifacts": [
            {
                "id": "source-a",
                "url": "synthetic://store-test/source-a",
                "retrieved_at": "2026-07-03T15:30:00Z",
                "evidence_grade": "A",
                "media_type": "application/json",
                "content_sha256": HASH_A,
                "snapshot_path": "embedded://source_artifacts/source-a",
                "license_note": "Locally generated synthetic test evidence.",
                "synthetic_content": True,
            }
        ],
        "capability_evidence": [
            {
                "id": "capability-a",
                "model_id": "model-a-v1",
                "endpoint_id": "endpoint-a-v1",
                "metric": "eci",
                "metric_version": "synthetic-v1",
                "score": "150.000",
                "configuration_id": "config-a-reasoning-disabled",
                "evaluated_at": "2026-07-01T00:00:00Z",
                "data_vintage": "2026-07-01",
                "source_id": "source-a",
                "evidence_grade": "A",
            }
        ],
        "token_counts": [
            {
                "id": "tokens-a-profile-100x100",
                "endpoint_id": "endpoint-a-v1",
                "profile_id": "structured-extraction",
                "input_tokens": 1000,
                "output_tokens": 100,
                "input_payload_sha256": HASH_A,
                "output_payload_sha256": HASH_B,
                "billing_tokenizer": "synthetic-counts-v1",
                "size_variant": "100x100",
                "input_payload_path": "kapi/profiles/input.json",
                "output_payload_path": "kapi/profiles/output.json",
            }
        ],
        "price_observations": [
            {
                "id": "price-a-input-v1",
                "endpoint_id": "endpoint-a-v1",
                "week_id": "week-2026-07-03",
                "component": "input",
                "amount_per_million": "2.500",
                "currency": "USD",
                "unit": "per_million_native_tokens",
                "region": "US",
                "tier": "standard",
                "context_min_tokens": 0,
                "context_max_tokens": 100000,
                "effective_at": "2026-07-03T00:00:00Z",
                "observed_at": "2026-07-03T15:30:00Z",
                "source_id": "source-a",
                "evidence_grade": "A",
            }
        ],
        "corrections": [],
        "methodology": {"id": "kapi-sw", "version": "0.1.0"},
    }


class StoreTests(unittest.TestCase):
    def setUp(self) -> None:
        self.connection = init_database(":memory:")

    def tearDown(self) -> None:
        self.connection.close()

    def test_round_trip_preserves_canonical_bundle_and_decimal_text(self) -> None:
        bundle = sample_bundle()

        ingest_bundle(self.connection, bundle)

        self.assertEqual(dump_bundle(self.connection), bundle)
        amount, storage_type = self.connection.execute(
            "SELECT amount_per_million, typeof(amount_per_million) "
            "FROM price_observations"
        ).fetchone()
        score, score_type = self.connection.execute(
            "SELECT score, typeof(score) FROM capability_evidence"
        ).fetchone()
        self.assertEqual((amount, storage_type), ("2.500", "text"))
        self.assertEqual((score, score_type), ("150.000", "text"))

    def test_superseding_observation_and_correction_are_new_rows(self) -> None:
        bundle = sample_bundle()
        replacement = copy.deepcopy(bundle["price_observations"][0])
        replacement.update(
            {
                "id": "price-a-input-v2",
                "amount_per_million": "2.250",
                "observed_at": "2026-07-03T15:45:00Z",
                "supersedes_observation_id": "price-a-input-v1",
            }
        )
        bundle["price_observations"].append(replacement)
        bundle["corrections"].append(
            {
                "id": "correction-price-a-v1",
                "detected_at": "2026-07-03T15:40:00Z",
                "impact": "Synthetic source typo.",
                "resolution": "Appended a replacement observation.",
                "approved_by": "test-reviewer",
                "new_vintage": "synthetic-store-test-v2",
                "superseded_observation_id": "price-a-input-v1",
                "replacement_observation_id": "price-a-input-v2",
                "affected_release_ids": [],
            }
        )

        ingest_bundle(self.connection, bundle)

        self.assertEqual(dump_bundle(self.connection), bundle)
        rows = self.connection.execute(
            "SELECT id, supersedes_observation_id FROM price_observations ORDER BY id"
        ).fetchall()
        self.assertEqual(
            [(row[0], row[1]) for row in rows],
            [("price-a-input-v1", None), ("price-a-input-v2", "price-a-input-v1")],
        )

    def test_database_triggers_block_updates_and_deletes(self) -> None:
        ingest_bundle(self.connection, sample_bundle())

        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute(
                "UPDATE price_observations SET amount_per_million = '1' "
                "WHERE id = 'price-a-input-v1'"
            )
        self.connection.rollback()
        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute("DELETE FROM source_artifacts WHERE id = 'source-a'")
        self.connection.rollback()
        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute("DELETE FROM datasets")
        self.connection.rollback()
        self.assertEqual(
            self.connection.execute(
                "SELECT amount_per_million FROM price_observations"
            ).fetchone()[0],
            "2.500",
        )

    def test_every_declared_append_only_table_has_both_triggers(self) -> None:
        triggers = {
            row[0]
            for row in self.connection.execute(
                "SELECT name FROM sqlite_master WHERE type = 'trigger'"
            )
        }
        for table in APPEND_ONLY_TABLES:
            with self.subTest(table=table):
                self.assertIn(f"{table}_no_update", triggers)
                self.assertIn(f"{table}_no_delete", triggers)

    def test_duplicate_ingest_is_rejected_without_changing_store(self) -> None:
        bundle = sample_bundle()
        ingest_bundle(self.connection, bundle)

        with self.assertRaisesRegex(StoreError, "conflicts with append-only store"):
            ingest_bundle(self.connection, bundle)

        self.assertEqual(dump_bundle(self.connection), bundle)

    def test_conflict_rolls_back_the_complete_bundle(self) -> None:
        bundle = sample_bundle()
        bundle["providers"].append(
            {"id": "provider-b", "name": "Provider A", "synthetic": True}
        )

        with self.assertRaises(StoreError):
            ingest_bundle(self.connection, bundle)

        self.assertEqual(
            self.connection.execute("SELECT count(*) FROM datasets").fetchone()[0], 0
        )
        self.assertEqual(
            self.connection.execute("SELECT count(*) FROM weeks").fetchone()[0], 0
        )

    def test_duplicate_ids_and_unlinked_conflicting_prices_are_rejected(self) -> None:
        duplicate = sample_bundle()
        duplicate["weeks"].append(copy.deepcopy(duplicate["weeks"][0]))
        with self.assertRaisesRegex(StoreError, "duplicate weeks id"):
            ingest_bundle(self.connection, duplicate)

        conflicting = sample_bundle()
        second = copy.deepcopy(conflicting["price_observations"][0])
        second.update(
            {
                "id": "price-a-input-conflict",
                "amount_per_million": "9.999",
                "observed_at": "2026-07-03T15:45:00Z",
            }
        )
        conflicting["price_observations"].append(second)
        with self.assertRaisesRegex(StoreError, "explicit single supersession chain"):
            ingest_bundle(self.connection, conflicting)
        self.assertEqual(
            self.connection.execute("SELECT count(*) FROM datasets").fetchone()[0], 0
        )

    def test_branching_price_supersession_is_rejected(self) -> None:
        bundle = sample_bundle()
        original = bundle["price_observations"][0]
        for suffix, amount in (("v2", "2.250"), ("v3", "2.125")):
            replacement = copy.deepcopy(original)
            replacement.update(
                {
                    "id": f"price-a-input-{suffix}",
                    "amount_per_million": amount,
                    "observed_at": "2026-07-03T15:45:00Z",
                    "supersedes_observation_id": original["id"],
                }
            )
            bundle["price_observations"].append(replacement)

        with self.assertRaisesRegex(StoreError, "explicit single supersession chain"):
            ingest_bundle(self.connection, bundle)
        self.assertEqual(
            self.connection.execute("SELECT count(*) FROM datasets").fetchone()[0], 0
        )

    def test_embedded_source_content_hash_is_enforced_by_store_api(self) -> None:
        bundle = sample_bundle()
        bundle["source_artifacts"][0]["synthetic_content"] = {
            "changed": True
        }

        with self.assertRaisesRegex(StoreError, "embedded snapshot bytes"):
            ingest_bundle(self.connection, bundle)
        self.assertEqual(
            self.connection.execute("SELECT count(*) FROM datasets").fetchone()[0], 0
        )

    def test_correction_supersession_cycle_is_rejected(self) -> None:
        bundle = sample_bundle()
        original = bundle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement.update(
            {
                "id": "price-a-input-v2",
                "amount_per_million": "2.250",
                "supersedes_observation_id": original["id"],
            }
        )
        bundle["price_observations"].append(replacement)
        bundle["corrections"] = [
            {
                "id": "correction-cycle-1",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-cycle-2",
            },
            {
                "id": "correction-cycle-2",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-cycle-1",
            },
        ]

        with self.assertRaisesRegex(StoreError, "contains a cycle"):
            ingest_bundle(self.connection, bundle)

    def test_token_ids_and_correction_links_are_required(self) -> None:
        missing_token_ids = sample_bundle()
        missing_token_ids["token_counts"][0].pop("id")
        with self.assertRaisesRegex(StoreError, r"token_counts\[0\]\.id"):
            ingest_bundle(self.connection, missing_token_ids)

        orphan_correction = sample_bundle()
        orphan_correction["corrections"] = [{"id": "orphan-correction"}]
        with self.assertRaisesRegex(
            StoreError, "superseded_observation_id"
        ):
            ingest_bundle(self.connection, orphan_correction)

    def test_calculation_and_release_lineage_is_foreign_keyed_and_append_only(self) -> None:
        ingest_bundle(self.connection, sample_bundle())
        self.connection.execute(
            "INSERT INTO methodology_versions VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                "method-v1", "1.0", "claim", "scope", "{}", "{}", "{}", "{}",
                "{}", "2026-01-01T00:00:00Z", None,
            ),
        )
        self.connection.execute(
            "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
            (
                "snapshot-v1", "synthetic-store-test-v1", "week-2026-07-03",
                "2026-07-03T16:00:00Z", "2026-07-03T16:05:00Z", HASH_A,
            ),
        )
        self.connection.execute(
            "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                "calculation-v1", "snapshot-v1", "method-v1", "1.0", "abc123",
                HASH_B, "2026-07-03T16:10:00Z", "complete", "100.0", "1.25", "{}",
            ),
        )
        self.connection.execute(
            "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
            (
                "release-v1", "calculation-v1", "week-2026-07-03", "v1",
                "provisional", "2026-07-06T15:00:00Z", "/kapi/releases/v1", None,
            ),
        )
        self.connection.commit()

        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute(
                "UPDATE calculations SET index_value = '99.9' WHERE id = 'calculation-v1'"
            )
        self.connection.rollback()
        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute("DELETE FROM releases WHERE id = 'release-v1'")
        self.connection.rollback()


if __name__ == "__main__":
    unittest.main()
