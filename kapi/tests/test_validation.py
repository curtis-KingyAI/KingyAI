from __future__ import annotations

import copy
import hashlib
import json
import unittest
from pathlib import Path
from urllib.parse import quote

from kapi.util import canonical_json_bytes
from kapi.validation import (
    find_forward_bundle_scalar_grammar_violations,
    find_input_claim_paths,
    forward_bundle_schema_definition_gaps,
    validate_bundle,
    validate_methodology,
)


ROOT = Path(__file__).resolve().parents[2]
HISTORICAL_BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
FORWARD_BUNDLE_PATH = (
    ROOT / "kapi/fixtures/synthetic-forward-governance-v0.3.0.json"
)
METHOD_PATH = ROOT / "kapi/config/methodology-v0.2.2.json"


class ValidationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = json.loads(FORWARD_BUNDLE_PATH.read_text(encoding="utf-8"))
        cls.historical_bundle = json.loads(
            HISTORICAL_BUNDLE_PATH.read_text(encoding="utf-8")
        )
        cls.methodology = json.loads(METHOD_PATH.read_text(encoding="utf-8"))

    def test_committed_methodology_and_bundle_are_valid(self) -> None:
        method_report = validate_methodology(self.methodology, repository_root=ROOT)
        bundle_report = validate_bundle(
            self.historical_bundle, self.methodology, repository_root=ROOT
        )
        self.assertTrue(method_report["valid"], method_report["errors"])
        self.assertTrue(bundle_report["valid"], bundle_report["errors"])
        self.assertEqual(60, method_report["stats"]["basket_count"])
        self.assertEqual(14, bundle_report["stats"]["weeks"])
        self.assertEqual(216, bundle_report["stats"]["token_counts"])

    def test_forward_governance_vintage_replaces_legacy_review_terminology(
        self,
    ) -> None:
        path = ROOT / "kapi/config/methodology-v0.3.0.json"
        forward = json.loads(path.read_text(encoding="utf-8"))
        report = validate_methodology(forward, repository_root=ROOT)
        self.assertTrue(report["valid"], report["errors"])
        self.assertNotIn("independent", json.dumps(forward).lower())
        self.assertNotIn("counts_verified", forward["construction_reference"])
        self.assertEqual(
            "verified_exact_local_reference_counts_only",
            forward["construction_reference"][
                "construction_reference_count_status"
            ],
        )
        self.assertNotIn(
            "approved_principles", forward["methodology_amendment"]
        )
        self.assertIn(
            "adopted_policy_principles", forward["methodology_amendment"]
        )

        extra_method_field = copy.deepcopy(forward)
        extra_method_field["human_review_status"] = "approved"
        method_report = validate_methodology(extra_method_field, repository_root=ROOT)
        self.assertFalse(method_report["valid"])
        self.assertIn(
            "forward_methodology_vintage",
            {error["code"] for error in method_report["errors"]},
        )

        claim_bearing_bundle = copy.deepcopy(self.bundle)
        claim_bearing_bundle["human_review_status"] = "approved"
        bundle_report = validate_bundle(
            claim_bearing_bundle, forward, repository_root=ROOT
        )
        self.assertFalse(bundle_report["valid"])
        self.assertTrue(
            {
                "unexpected_forward_bundle_field",
                "input_governance_claim",
            }.issubset({error["code"] for error in bundle_report["errors"]})
        )
        self.assertEqual(
            forward["governance_policy"]["current_review_label"],
            "Governance status: Unreviewed draft. Automated validation completed "
            "for this artifact; no operator or external methodology review is "
            "complete.",
        )
        self.assertEqual(self.bundle["schema_version"], "kapi-bundle-v0.3.0")
        self.assertNotIn("base_period_week_ids", self.bundle)
        self.assertNotIn("current_week_id", self.bundle)
        self.assertNotIn("expected_result", self.bundle)
        self.assertNotIn("generation", self.bundle)
        self.assertEqual(
            self.bundle["methodology"],
            {
                "config_path": "kapi/config/methodology-v0.3.0.json",
                "config_sha256": hashlib.sha256(path.read_bytes()).hexdigest(),
                "id": forward["methodology_id"],
                "version": "0.3.0",
            },
        )

        binding_attacks = (
            ("id", "not-kapi"),
            ("version", "9.9.9"),
            ("config_path", "kapi/config/other.json"),
            ("config_sha256", "not-a-sha"),
        )
        for field, value in binding_attacks:
            with self.subTest(binding_field=field):
                attacked = copy.deepcopy(self.bundle)
                attacked["methodology"][field] = value
                attacked_report = validate_bundle(
                    attacked, forward, repository_root=ROOT
                )
                self.assertFalse(attacked_report["valid"])
                self.assertIn(
                    "forward_bundle_binding", attacked_report["issue_counts"]
                )

        oracle_attack = copy.deepcopy(self.bundle)
        oracle_attack["expected_result"] = {
            "strict_headline": {
                "status": "approved",
                "reason": "all_gates_passed",
            }
        }
        oracle_report = validate_bundle(
            oracle_attack, forward, repository_root=ROOT
        )
        self.assertFalse(oracle_report["valid"])
        self.assertIn("forward_bundle_binding", oracle_report["issue_counts"])
        self.assertIn("unexpected_forward_bundle_field", oracle_report["issue_counts"])
        self.assertIn("input_governance_claim", oracle_report["issue_counts"])

        generation_attack = copy.deepcopy(self.bundle)
        generation_attack["generation"] = {
            "external_dependencies": ["OpenAI paid API"],
            "generator_path": "remote-model",
            "model_calls_performed": 5,
            "network_access_used": True,
            "paid_calls_performed": 5,
            "total_external_spend_usd": "123.45",
        }
        generation_report = validate_bundle(
            generation_attack, forward, repository_root=ROOT
        )
        self.assertFalse(generation_report["valid"])
        self.assertIn("forward_bundle_binding", generation_report["issue_counts"])
        self.assertIn(
            "unexpected_forward_bundle_field", generation_report["issue_counts"]
        )

        id_attack = copy.deepcopy(self.bundle)
        id_attack["endpoints"][0]["id"] = "operator"
        id_report = validate_bundle(id_attack, forward, repository_root=ROOT)
        self.assertFalse(id_report["valid"])
        self.assertIn("forward_id_namespace", id_report["issue_counts"])

        forged = copy.deepcopy(forward)
        forged["readiness_gates"]["trusted_verifier_adapter"] = "passed"
        forged["governance_policy"]["external_methodology_review"] = "complete"
        forged["governance_policy"]["publication_eligible"] = True
        forged_report = validate_methodology(forged, repository_root=ROOT)
        self.assertFalse(forged_report["valid"])
        self.assertIn("readiness_gates", forged_report["issue_counts"])
        self.assertIn("governance_policy", forged_report["issue_counts"])

    def test_forward_bundle_claim_keys_and_prose_fail_without_overblocking(
        self,
    ) -> None:
        forward = json.loads(
            (ROOT / "kapi/config/methodology-v0.3.0.json").read_text(encoding="utf-8")
        )
        self.assertEqual([], forward_bundle_schema_definition_gaps())
        attacks = (
            (
                "audit-note value",
                ("source_artifacts", 0, "audit_note"),
                "KAPI is independently reviewed and approved.",
            ),
            (
                "note value",
                ("source_artifacts", 0, "note"),
                "Governance status: Operator-reviewed by Kingy.ai and ready "
                "for publication.",
            ),
            (
                "reviewed-by alias",
                ("weeks", 0, "reviewed_by"),
                "Independent external reviewer",
            ),
            (
                "known license-note value",
                ("source_artifacts", 0, "license_note"),
                "KAPI is externally reviewed and authorized.",
            ),
            (
                "known provider-name value",
                ("providers", 0, "name"),
                "Operator-reviewed and approved KAPI",
            ),
            (
                "known generation-status value",
                ("generation",),
                {"status": "Governance status: ready for publication"},
            ),
            (
                "fullwidth and combining marks",
                ("source_artifacts", 0, "note"),
                "ＫＡＰＩ is ｉｎｄｅｐｅｎｄｅｎｔｌｙ "
                "re\u0301viewed and appro\u0301ved.",
            ),
            (
                "common confusables",
                ("source_artifacts", 0, "note"),
                "KAPI is іndереndently rеνіеwеd and approved.",
            ),
            (
                "nested arrays",
                ("source_artifacts", 0, "audit_notes"),
                [["KAPI is operator reviewed and approved"]],
            ),
            (
                "normalized near-alias key",
                ("weeks", 0, "Ｒｅｖｉｅｗｅｄ＿Ｂｙ"),
                "Jane Example",
            ),
            (
                "punctuated publication alias",
                ("generation",),
                {"publication-status": "not authorized"},
            ),
            (
                "bare reviewer key",
                ("source_artifacts", 0, "reviewer"),
                "Independent Expert",
            ),
            (
                "bare reviewers key",
                ("source_artifacts", 0, "reviewers"),
                ["Jane Example"],
            ),
            (
                "reviewer-name alias",
                ("source_artifacts", 0, "reviewer_name"),
                "Jane Example",
            ),
            (
                "certified-by alias",
                ("source_artifacts", 0, "certified_by"),
                "Jane Example",
            ),
            (
                "auditor alias",
                ("source_artifacts", 0, "auditor"),
                "Jane Example",
            ),
            (
                "audit-status alias",
                ("source_artifacts", 0, "audit_status"),
                "not started",
            ),
            (
                "approval alias",
                ("source_artifacts", 0, "approval"),
                "none",
            ),
            (
                "verified-by alias",
                ("source_artifacts", 0, "verified_by"),
                "Jane Example",
            ),
            (
                "signatory alias",
                ("source_artifacts", 0, "signatory"),
                "Jane Example",
            ),
            (
                "signed-by alias",
                ("source_artifacts", 0, "signed_by"),
                "Jane Example",
            ),
            (
                "assurance-status alias",
                ("source_artifacts", 0, "assurance_status"),
                "none",
            ),
            (
                "deployment alias",
                ("source_artifacts", 0, "deployed"),
                False,
            ),
            (
                "go-live alias",
                ("source_artifacts", 0, "go_live"),
                False,
            ),
            (
                "claim-key confusables",
                ("source_artifacts", 0, "rеνіеwer"),
                "Jane Example",
            ),
            (
                "value-only go-live readiness",
                ("source_artifacts", 0, "note"),
                "KAPI cleared to go live",
            ),
            (
                "value-only generic verification badge",
                ("source_artifacts", 0, "note"),
                "KAPI verified",
            ),
            (
                "value-only completed verification badge",
                ("source_artifacts", 0, "note"),
                "KAPI verification complete",
            ),
            (
                "value-only reviewer attribution",
                ("source_artifacts", 0, "note"),
                "KAPI reviewer: Jane",
            ),
            (
                "value-only generic assurance",
                ("source_artifacts", 0, "note"),
                "KAPI is independent",
            ),
            (
                "value-only operator attribution",
                ("source_artifacts", 0, "note"),
                "KAPI operator checked",
            ),
            (
                "value-only bare readiness",
                ("source_artifacts", 0, "note"),
                "KAPI ready",
            ),
            (
                "value-only published state",
                ("source_artifacts", 0, "note"),
                "KAPI published",
            ),
            (
                "nested KAPI status map",
                ("source_artifacts", 0, "kapi"),
                {"status": "verified"},
            ),
            (
                "sibling split map",
                ("source_artifacts", 0, "source_metadata"),
                {"subject": "KAPI", "status": "verified"},
            ),
            (
                "split string list",
                ("source_artifacts", 0, "source_metadata"),
                ["KAPI", "verified"],
            ),
            (
                "split nested list",
                ("source_artifacts", 0, "source_metadata"),
                ["KAPI", {"status": "ready"}],
            ),
            (
                "split allowed-field list",
                ("endpoints", 0, "features"),
                ["KAPI", "verified"],
            ),
            (
                "split operator review list",
                ("endpoints", 0, "features"),
                ["operator", "reviewed"],
            ),
            (
                "split independent review list",
                ("endpoints", 0, "features"),
                ["independent", "reviewed"],
            ),
            (
                "split external review list",
                ("endpoints", 0, "features"),
                ["external", "reviewed"],
            ),
            (
                "split external reviewer list",
                ("endpoints", 0, "features"),
                ["independent", "external", "reviewer"],
            ),
        )
        for label, segments, value in attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = value
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])
                self.assertTrue(find_input_claim_paths(attacked))

        mapping_split_attacks = tuple(
            attack
            for actor in ("operator", "independent", "external")
            for attack in (
                (
                    f"{actor} URL plus reviewed license",
                    {
                        "url": f"https://example.invalid/{actor}",
                        "license_note": "reviewed",
                    },
                ),
                (
                    f"{actor} license plus reviewed media type",
                    {"license_note": actor, "media_type": "reviewed"},
                ),
            )
        )
        for label, updates in mapping_split_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                claim_paths = find_input_claim_paths(attacked)
                self.assertIn("bundle.source_artifacts[0]", claim_paths)
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        encoded_mapping_attacks = (
            (
                "percent-encoded operator in URL",
                {
                    "url": "https://example.invalid/op%65rator",
                    "license_note": "reviewed",
                },
            ),
            (
                "percent-encoded review in URL",
                {
                    "url": "https://example.invalid/rev%69ewed",
                    "license_note": "operator",
                },
            ),
            (
                "double-percent operator fragment in URL",
                {
                    "url": "https://example.invalid/op%2565rator",
                    "license_note": "reviewed",
                },
            ),
            (
                "HTML-entity operator in license",
                {"license_note": "op&#101;rator", "media_type": "text/reviewed"},
            ),
            (
                "HTML-entity review in one license value",
                {"license_note": "operator rev&#105;ewed"},
            ),
            (
                "percent-encoded independent in URL",
                {
                    "url": "https://example.invalid/%69ndependent",
                    "license_note": "reviewed",
                },
            ),
            (
                "fully double-percent-encoded operator in URL",
                {
                    "url": "https://example.invalid/"
                    "%256f%2570%2565%2572%2561%2574%256f%2572",
                    "license_note": "reviewed",
                },
            ),
            (
                "fully numeric-entity operator in license",
                {
                    "license_note": "&#111;&#112;&#101;&#114;&#097;&#116;&#111;&#114;",
                    "media_type": "text/reviewed",
                },
            ),
            (
                "double-HTML-entity operator in license",
                {
                    "license_note": "op&amp;#101;rator",
                    "media_type": "text/reviewed",
                },
            ),
            (
                "encoded review in URL query",
                {
                    "url": "https://example.invalid/source?state=rev%69ewed",
                    "license_note": "operator",
                },
            ),
            (
                "encoded operator in URL fragment",
                {
                    "url": "https://example.invalid/source#op%65rator",
                    "license_note": "reviewed",
                },
            ),
        )
        for label, updates in encoded_mapping_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                claim_paths = find_input_claim_paths(attacked)
                self.assertIn("bundle.source_artifacts[0]", claim_paths)
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        semantic_claims = (
            "approved",
            "verified",
            "audited",
            "reviewed",
            "reviewer",
            "signoff",
            "certified",
            "authorized",
            "ready",
            "passed",
            "status: approved",
            "status=verified",
            "ready to go",
            "greenlit",
            "cleared",
            "published",
            "live",
            "reviewed by Jane Example",
            "reviewed by an outside expert",
            "outside expert reviewed",
            "expert reviewed",
            "peer reviewed",
            "human reviewed",
            "editor reviewed",
            "audited by Jane",
            "certified by assessor",
            "certified by consultant",
            "attested by consultant",
            "signed by consultant",
            "review complete",
            "externally checked",
        )
        for claim in semantic_claims:
            with self.subTest(semantic_claim=claim):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0]["license_note"] = claim
                self.assertIn(
                    "bundle.source_artifacts[0].license_note",
                    find_input_claim_paths(attacked),
                )
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        encoded_leaf_attacks = (
            (
                "percent-encoded KAPI verification",
                ("source_artifacts", 0, "license_note"),
                "K%41PI ver%69fied",
            ),
            (
                "double-percent KAPI review",
                ("source_artifacts", 0, "license_note"),
                "K%2541PI%2520rev%2569ewed",
            ),
            (
                "double-entity KAPI readiness",
                ("source_artifacts", 0, "license_note"),
                "K&amp;#65;PI ready",
            ),
            (
                "small-cap-v KAPI review",
                ("source_artifacts", 0, "license_note"),
                "KAPI reᴠiewed",
            ),
            (
                "small-cap-i KAPI review",
                ("source_artifacts", 0, "license_note"),
                "KAPI revɪewed",
            ),
            (
                "literal Unicode escape KAPI verification",
                ("source_artifacts", 0, "license_note"),
                r"KAPI \u0076erified",
            ),
            (
                "encoded provider-name assertion",
                ("providers", 0, "name"),
                "op%65rator rev%69ewed",
            ),
            (
                "encoded list assertion",
                ("endpoints", 0, "features"),
                ["K%41PI", "rev%69ewed"],
            ),
            (
                "percent-encoded claim key",
                ("source_artifacts", 0, "r%65viewer"),
                "Jane Example",
            ),
            (
                "double-entity claim key",
                ("source_artifacts", 0, "r&amp;#101;viewer"),
                "Jane Example",
            ),
        )
        for label, segments, value in encoded_leaf_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = value
                self.assertTrue(find_input_claim_paths(attacked))
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        semantic_split_lists = (
            ["reviewed by", "Jane Example"],
            ["outside expert", "reviewed"],
            ["audited", "by Jane"],
            ["certified", "by assessor"],
        )
        for carrier in semantic_split_lists:
            with self.subTest(semantic_split_list=carrier):
                attacked = copy.deepcopy(self.bundle)
                attacked["endpoints"][0]["features"] = carrier
                self.assertIn(
                    "bundle.endpoints[0].features",
                    find_input_claim_paths(attacked),
                )
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        semantic_split_mappings = (
            {
                "url": "https://example.invalid/audited",
                "license_note": "by Jane",
            },
            {"license_note": "expert", "media_type": "text/reviewed"},
            {
                "license_note": "KAPI status",
                "url": "https://example.invalid/verified",
            },
            {
                "license_note": "publication status",
                "url": "https://example.invalid/ready",
            },
            {
                "license_note": "KAPI status",
                "url": "https://example.invalid/independent",
            },
        )
        for updates in semantic_split_mappings:
            with self.subTest(semantic_split_mapping=updates):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                self.assertIn(
                    "bundle.source_artifacts[0]",
                    find_input_claim_paths(attacked),
                )
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("input_governance_claim", report["issue_counts"])

        invalid_media_type = copy.deepcopy(self.bundle)
        invalid_media_type["source_artifacts"][0]["media_type"] = "reviewed"
        report = validate_bundle(
            invalid_media_type, forward, repository_root=ROOT
        )
        self.assertFalse(report["valid"])
        self.assertIn("source_metadata", report["issue_counts"])
        self.assertIn("input_governance_claim", report["issue_counts"])

        cross_depth_split = copy.deepcopy(self.bundle)
        cross_depth_artifact = next(
            artifact
            for artifact in cross_depth_split["source_artifacts"]
            if "regime" in artifact["synthetic_content"]
        )
        cross_depth_artifact["license_note"] = "KAPI"
        cross_depth_artifact["synthetic_content"]["regime"] = "verified"
        report = validate_bundle(cross_depth_split, forward, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("input_governance_claim", report["issue_counts"])
        self.assertTrue(
            any(
                path.startswith("bundle.source_artifacts[")
                for path in find_input_claim_paths(cross_depth_split)
            )
        )

        undeclared_neutral = copy.deepcopy(self.bundle)
        undeclared_neutral["source_artifacts"][0]["source_metadata"] = (
            "ordinary retained provenance note"
        )
        report = validate_bundle(undeclared_neutral, forward, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("unexpected_forward_bundle_field", report["issue_counts"])
        self.assertNotIn("input_governance_claim", report["issue_counts"])

        undeclared_nested_object = copy.deepcopy(self.bundle)
        undeclared_nested_object["providers"][0]["name"] = {
            "ordinary": "provenance"
        }
        report = validate_bundle(
            undeclared_nested_object, forward, repository_root=ROOT
        )
        self.assertFalse(report["valid"])
        self.assertIn("unexpected_forward_bundle_field", report["issue_counts"])

        formerly_benign = copy.deepcopy(self.bundle)
        formerly_benign["source_artifacts"][0]["license_note"] = (
            "Source hash verified against retained bytes; license reviewed for "
            "archival completeness."
        )
        formerly_benign["providers"][0]["name"] = "Audit Trail Research"
        formerly_benign["creators"][0]["name"] = "Approved License Research"
        report = validate_bundle(formerly_benign, forward, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("forward_scalar_grammar", report["issue_counts"])
        self.assertNotIn("input_governance_claim", report["issue_counts"])
        self.assertIn("diverse", forward["selection"]["method"])

    def test_forward_scalar_grammar_closes_every_public_string_carrier(
        self,
    ) -> None:
        forward = json.loads(
            (ROOT / "kapi/config/methodology-v0.3.0.json").read_text(encoding="utf-8")
        )
        claims = (
            "fully approved",
            "approval granted",
            "approved status",
            "certified compliant",
            "authorization granted",
            "has passed",
            "go live",
            "production ready",
            "launch cleared",
            "externally validated",
            "validated by Jane",
            "vetted by expert",
            "assessed by consultant",
            "publication eligible",
            "publication allowed",
            "Jane completed the review",
            "Jane signed",
            "Jane attested",
            "Jane endorsed",
            "OK to release",
            "clear to launch",
            "QA passed",
            "not unreviewed",
            "not unverified",
            "no longer unreviewed",
            "unreviewed? no",
            "not without review",
            "KAPI rev1ewed",
            "KAPI v3rified",
            "KAPI ver1fied",
            "KAPI appr0ved",
        )
        price_source_index = next(
            index
            for index, artifact in enumerate(self.bundle["source_artifacts"])
            if "regime" in artifact["synthetic_content"]
        )
        for claim in claims:
            for carrier in ("license_note", "synthetic_content.regime"):
                with self.subTest(claim=claim, carrier=carrier):
                    attacked = copy.deepcopy(self.bundle)
                    if carrier == "license_note":
                        attacked["source_artifacts"][0]["license_note"] = claim
                        expected_path = "bundle.source_artifacts[0].license_note"
                    else:
                        artifact = attacked["source_artifacts"][price_source_index]
                        artifact["synthetic_content"]["regime"] = claim
                        artifact["content_sha256"] = hashlib.sha256(
                            canonical_json_bytes(artifact["synthetic_content"])
                        ).hexdigest()
                        expected_path = (
                            f"bundle.source_artifacts[{price_source_index}]"
                            ".synthetic_content.regime"
                        )
                    self.assertIn(
                        expected_path,
                        find_forward_bundle_scalar_grammar_violations(attacked),
                    )

        encoded = "".join(f"%{ord(character):02X}" for character in "approved")
        for _ in range(4):
            encoded = quote(encoded, safe="")
        residual_attacks = (
            encoded,
            "&amp;amp;amp;amp;#97;pproved",
            r"%255C%2575%2530%2530%2536%2531pproved",
        )
        for encoded_claim in residual_attacks:
            with self.subTest(residual_encoding=encoded_claim):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0]["license_note"] = encoded_claim
                self.assertIn(
                    "bundle.source_artifacts[0].license_note",
                    find_input_claim_paths(attacked),
                )
                report = validate_bundle(
                    attacked,
                    forward,
                    repository_root=ROOT,
                )
                self.assertFalse(report["valid"])
                self.assertIn("forward_scalar_grammar", report["issue_counts"])
                self.assertIn("input_governance_claim", report["issue_counts"])

    def test_forward_object_keysets_are_exact_and_omissions_fail_closed(
        self,
    ) -> None:
        forward = json.loads(
            (ROOT / "kapi/config/methodology-v0.3.0.json").read_text(encoding="utf-8")
        )
        deletion_paths = (
            ("providers", 0, "synthetic"),
            ("providers", 0, "name"),
            ("creators", 0, "name"),
            ("models", 0, "version"),
            ("endpoints", 0, "tokenizer_reproducible"),
            ("endpoints", 0, "available_until"),
            ("token_counts", 0, "synthetic_count_note"),
        )
        for segments in deletion_paths:
            with self.subTest(deleted_path=segments):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                del target[segments[-1]]
                self.assertTrue(
                    find_forward_bundle_scalar_grammar_violations(attacked)
                )
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn(
                    "missing_forward_bundle_field", report["issue_counts"]
                )
                self.assertIn("forward_scalar_grammar", report["issue_counts"])

        derived_field_attacks = {
            "base_period_week_ids": [
                week["id"] for week in self.bundle["weeks"][:13]
            ],
            "current_week_id": self.bundle["weeks"][-1]["id"],
        }
        for field, value in derived_field_attacks.items():
            with self.subTest(redundant_derived_field=field):
                attacked = copy.deepcopy(self.bundle)
                attacked[field] = value
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn(
                    "unexpected_forward_bundle_field", report["issue_counts"]
                )
                self.assertIn("bounded_fixture_identity", report["issue_counts"])

        source_variants = tuple(
            artifact
            for artifact in self.bundle["source_artifacts"]
            if "capability_scope" in artifact["synthetic_content"]
        )[:1] + tuple(
            artifact
            for artifact in self.bundle["source_artifacts"]
            if "regime" in artifact["synthetic_content"]
        )[:1]
        for source_template in source_variants:
            for field in source_template["synthetic_content"]:
                with self.subTest(source_id=source_template["id"], deleted=field):
                    attacked = copy.deepcopy(self.bundle)
                    artifact = next(
                        item
                        for item in attacked["source_artifacts"]
                        if item["id"] == source_template["id"]
                    )
                    del artifact["synthetic_content"][field]
                    artifact["content_sha256"] = hashlib.sha256(
                        canonical_json_bytes(artifact["synthetic_content"])
                    ).hexdigest()
                    self.assertTrue(
                        find_forward_bundle_scalar_grammar_violations(attacked)
                    )
                    report = validate_bundle(
                        attacked, forward, repository_root=ROOT
                    )
                    self.assertFalse(report["valid"])
                    self.assertTrue(
                        {
                            "missing_forward_bundle_field",
                            "forward_record_variant",
                        }
                        & set(report["issue_counts"])
                    )
        price_source_index = next(
            index
            for index, artifact in enumerate(self.bundle["source_artifacts"])
            if "regime" in artifact["synthetic_content"]
        )
        type_attacks = (
            (("providers", 0, "name"), True),
            (("providers", 0, "name"), None),
            (("providers", 0, "name"), 7),
            (("token_counts", 0, "synthetic_count_note"), True),
            (("token_counts", 0, "synthetic_count_note"), None),
            (("token_counts", 0, "synthetic_count_note"), 7),
            (("endpoints", 0, "features", 0), True),
            (("endpoints", 0, "features", 0), None),
            (("endpoints", 0, "features", 0), 7),
        )
        for segments, replacement in type_attacks:
            with self.subTest(type_path=segments, replacement=replacement):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = replacement
                self.assertTrue(
                    find_forward_bundle_scalar_grammar_violations(attacked)
                )
                report = validate_bundle(
                    attacked,
                    forward,
                    repository_root=ROOT,
                )
                self.assertFalse(report["valid"])
                self.assertIn("forward_scalar_grammar", report["issue_counts"])

        for replacement in (True, None, 7):
            with self.subTest(regime_type=replacement):
                attacked = copy.deepcopy(self.bundle)
                artifact = attacked["source_artifacts"][price_source_index]
                artifact["synthetic_content"]["regime"] = replacement
                artifact["content_sha256"] = hashlib.sha256(
                    canonical_json_bytes(artifact["synthetic_content"])
                ).hexdigest()
                self.assertIn(
                    f"bundle.source_artifacts[{price_source_index}]"
                    ".synthetic_content.regime",
                    find_forward_bundle_scalar_grammar_violations(attacked),
                )
                report = validate_bundle(
                    attacked,
                    forward,
                    repository_root=ROOT,
                )
                self.assertFalse(report["valid"])
                self.assertIn("forward_scalar_grammar", report["issue_counts"])

    def test_bounded_forward_fixture_identity_rejects_in_grammar_permutations(
        self,
    ) -> None:
        forward = json.loads(
            (ROOT / "kapi/config/methodology-v0.3.0.json").read_text(encoding="utf-8")
        )
        attacks: list[tuple[str, dict]] = []

        provider_swap = copy.deepcopy(self.bundle)
        provider_swap["providers"][0]["name"] = provider_swap["providers"][1][
            "name"
        ]
        attacks.append(("provider name swap", provider_swap))

        model_version_swap = copy.deepcopy(self.bundle)
        model_version_swap["models"][0]["version"] = model_version_swap["models"][
            1
        ]["version"]
        attacks.append(("model version swap", model_version_swap))

        capability_source_change = copy.deepcopy(self.bundle)
        capability_source = next(
            item
            for item in capability_source_change["source_artifacts"]
            if "capability_scope" in item["synthetic_content"]
        )
        capability_source["synthetic_content"]["score"] = "149"
        capability_source["content_sha256"] = hashlib.sha256(
            canonical_json_bytes(capability_source["synthetic_content"])
        ).hexdigest()
        attacks.append(("hash-repaired capability source", capability_source_change))

        price_source_change = copy.deepcopy(self.bundle)
        price_source = next(
            item
            for item in price_source_change["source_artifacts"]
            if "regime" in item["synthetic_content"]
        )
        price_source["synthetic_content"]["input_amount_per_million"] = "999"
        price_source["content_sha256"] = hashlib.sha256(
            canonical_json_bytes(price_source["synthetic_content"])
        ).hexdigest()
        attacks.append(("hash-repaired price source", price_source_change))

        for label, attacked in attacks:
            with self.subTest(identity_permutation=label):
                self.assertEqual(
                    [], find_forward_bundle_scalar_grammar_violations(attacked)
                )
                self.assertEqual([], find_input_claim_paths(attacked))
                report = validate_bundle(attacked, forward, repository_root=ROOT)
                self.assertFalse(report["valid"])
                self.assertIn("bounded_fixture_identity", report["issue_counts"])

    def test_forward_contract_rejects_methodology_and_schema_downgrades(
        self,
    ) -> None:
        forward_method = json.loads(
            (ROOT / "kapi/config/methodology-v0.3.0.json").read_text(
                encoding="utf-8"
            )
        )
        for version in ("0.2.0", "0.2.1", "0.2.2"):
            with self.subTest(older_methodology=version):
                older_method = json.loads(
                    (ROOT / f"kapi/config/methodology-v{version}.json").read_text(
                        encoding="utf-8"
                    )
                )
                report = validate_bundle(
                    self.bundle, older_method, repository_root=ROOT
                )
                self.assertFalse(report["valid"])
                self.assertIn("forward_methodology_pair", report["issue_counts"])
                self.assertIn("forward_bundle_binding", report["issue_counts"])

                for dataset_id in (
                    "synthetic-hand-example-v1",
                    "arbitrary-legacy-looking-dataset",
                ):
                    with self.subTest(
                        full_marker_rewrite=(version, dataset_id)
                    ):
                        rewritten = copy.deepcopy(self.bundle)
                        rewritten["schema_version"] = "kapi-bundle-v0.1.0"
                        rewritten["dataset_id"] = dataset_id
                        rewritten["methodology"] = {
                            "config_path": (
                                f"kapi/config/methodology-v{version}.json"
                            ),
                            "config_sha256": hashlib.sha256(
                                canonical_json_bytes(older_method)
                            ).hexdigest(),
                            "id": older_method["methodology_id"],
                            "version": version,
                        }
                        report = validate_bundle(
                            rewritten, older_method, repository_root=ROOT
                        )
                        self.assertFalse(report["valid"])
                        self.assertIn(
                            "historical_fixture_identity",
                            report["issue_counts"],
                        )

        report = validate_bundle(
            self.historical_bundle, forward_method, repository_root=ROOT
        )
        self.assertFalse(report["valid"])
        self.assertIn("forward_bundle_binding", report["issue_counts"])

        variants = (
            ("kapi-bundle-v0.3.1", forward_method),
            (
                "kapi-bundle-v0.2.2",
                json.loads(
                    (ROOT / "kapi/config/methodology-v0.2.2.json").read_text(
                        encoding="utf-8"
                    )
                ),
            ),
        )
        for schema_version, supplied_method in variants:
            with self.subTest(schema_version=schema_version):
                attacked = copy.deepcopy(self.bundle)
                attacked["schema_version"] = schema_version
                report = validate_bundle(
                    attacked, supplied_method, repository_root=ROOT
                )
                self.assertFalse(report["valid"])
                self.assertIn("forward_bundle_binding", report["issue_counts"])

    def test_v022_portability_contract_fails_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["construction_manifest"]["entry_count"] = 11
        method["construction_reference"]["portable_reproduction"][
            "full_source_asset_required"
        ] = True
        method["construction_reference"]["full_source_asset_path_configuration"][
            "repository_default"
        ] = "/workstation/path"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("construction_manifest", report["issue_counts"])
        self.assertIn("construction_portability", report["issue_counts"])
        self.assertIn("construction_source_path", report["issue_counts"])

    def test_changed_payload_bytes_fail_hash_validation(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["input_payload_sha256"] = "0" * 64
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])

    def test_duplicate_identity_and_broken_reference_fail(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["providers"].append(copy.deepcopy(bundle["providers"][0]))
        bundle["models"][0]["creator_id"] = "creator-does-not-exist"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("duplicate_id", report["issue_counts"])
        self.assertIn("unknown_reference", report["issue_counts"])

    def test_unresolved_conflicting_price_observation_fails(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        conflict = copy.deepcopy(bundle["price_observations"][0])
        conflict["id"] += "-conflict"
        conflict["amount_per_million"] = "999"
        bundle["price_observations"].append(conflict)
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("conflicting_observation", report["issue_counts"])

    def test_grade_and_decimal_must_be_valid(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["source_artifacts"][0]["evidence_grade"] = "Z"
        bundle["price_observations"][0]["amount_per_million"] = "NaN"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("evidence_grade", report["issue_counts"])
        self.assertIn("invalid_decimal", report["issue_counts"])

    def test_embedded_source_content_must_match_retained_hash(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["source_artifacts"][0]["synthetic_content"]["score"] = "999"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("source_content_hash", report["issue_counts"])

    def test_capability_and_token_records_must_match_linked_records(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        capability = bundle["capability_evidence"][0]
        capability["model_id"] = bundle["models"][1]["id"]
        capability["configuration_id"] = "different-configuration"
        capability["evidence_grade"] = "B"
        bundle["token_counts"][0]["input_payload_sha256"] = "0" * 64
        bundle["token_counts"][1]["id"] = bundle["token_counts"][0]["id"]
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("capability_endpoint", report["issue_counts"])
        self.assertIn("source_grade", report["issue_counts"])
        self.assertIn("token_payload", report["issue_counts"])
        self.assertIn("duplicate_id", report["issue_counts"])

    def test_v021_evidence_classes_and_candidates_fail_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["evidence_classes"]["billed_usage_counts"]["status"] = "verified"
        method["endpoint_specific_billing_counts"]["verified_billing_rows"] = 1
        method["candidate_configurations"][1]["model_id"] = "gemini-2.5-pro"
        method["readiness_gates"]["technical_go"] = "passed"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("evidence_classes", report["issue_counts"])
        self.assertIn("billing_counts_unverified", report["issue_counts"])
        self.assertIn("candidate_substitution", report["issue_counts"])
        self.assertIn("readiness_gates", report["issue_counts"])

    def test_v021_official_documentation_cannot_promote_runtime_evidence(self) -> None:
        method = copy.deepcopy(self.methodology)
        candidates = {
            row["candidate_id"]: row for row in method["candidate_configurations"]
        }
        openai = candidates["openai-gpt54mini-reasoning-none"]
        openai["official_documentation"]["status"] = (
            "supported_configuration_and_pricing"
        )
        openai["eligibility_status"] = "review_candidate_only"
        anthropic = candidates["anthropic-claude-sonnet-4-6-thinking-omitted"]
        anthropic["priced_configuration"] = {"thinking": "disabled"}
        candidates["google-gemini25flash-thinking-budget-0"][
            "provider_preflight_status"
        ] = "verified"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("candidate_official_evidence", report["issue_counts"])
        self.assertIn("candidate_runtime_evidence", report["issue_counts"])

    def test_v021_evidence_record_hash_is_frozen(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["official_provider_evidence"]["record_sha256"] = "0" * 64
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])

    def test_v020_payload_and_token_count_evidence_fail_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["payloads"]["input"][0]["construction_token_count"] += 1
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_construction_count", report["issue_counts"])

        bundle = copy.deepcopy(self.bundle)
        bundle["token_counts"][0]["billing_usage_count_status"] = "verified"
        bundle["token_counts"][0]["construction_count_evidence_class"] = "billing"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("billing_counts_unverified", report["issue_counts"])
        self.assertIn("evidence_class_separation", report["issue_counts"])

    def test_alternate_payloads_and_grid_ids_are_verified(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["payloads"]["input"][0]["sha256"] = "0" * 64
        method["sensitivities"]["payload_size_grid"][0]["id"] = "wrong-grid-id"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])
        self.assertIn("payload_grid", report["issue_counts"])

    def test_binary_floats_and_mixed_dataset_kind_are_rejected(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["dataset_kind"] = "mixed"
        bundle["price_observations"][0]["amount_per_million"] = 2.5
        bundle["capability_evidence"][0]["score"] = 150.0
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("dataset_kind", report["issue_counts"])
        self.assertGreaterEqual(report["issue_counts"].get("invalid_decimal", 0), 2)

    def test_supersession_cycles_and_invalid_correction_links_fail(self) -> None:
        price_cycle = copy.deepcopy(self.bundle)
        original = price_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = f"{original['id']}-replacement"
        original["supersedes_observation_id"] = replacement["id"]
        replacement["supersedes_observation_id"] = original["id"]
        price_cycle["price_observations"].append(replacement)
        report = validate_bundle(price_cycle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("supersession_cycle", report["issue_counts"])

        correction_cycle = copy.deepcopy(self.bundle)
        original = correction_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = f"{original['id']}-replacement"
        replacement["supersedes_observation_id"] = original["id"]
        correction_cycle["price_observations"].append(replacement)
        correction_cycle["corrections"] = [
            {
                "id": "correction-1",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-2",
            },
            {
                "id": "correction-2",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-1",
            },
        ]
        report = validate_bundle(
            correction_cycle, self.methodology, repository_root=ROOT
        )
        self.assertFalse(report["valid"])
        self.assertIn("supersession_cycle", report["issue_counts"])

        invalid_link = copy.deepcopy(correction_cycle)
        invalid_link["corrections"] = [invalid_link["corrections"][0]]
        invalid_link["corrections"][0].pop("supersedes_correction_id")
        invalid_link["price_observations"][-1]["effective_at"] = "2026-04-03T00:00:01Z"
        report = validate_bundle(invalid_link, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("correction_linkage", report["issue_counts"])


if __name__ == "__main__":
    unittest.main()
