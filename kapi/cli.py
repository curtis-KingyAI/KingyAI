"""Command-line interface for the isolated KAPI research prototype."""

from __future__ import annotations

import argparse
import sys
from pathlib import Path
from typing import Any, Sequence

from .calculation import CalculationError, calculate_index
from .exporter import ExportError, export_release, reproduce_release
from .store import StoreError, dump_bundle, ingest_bundle, init_database
from .util import canonical_json_bytes, load_json, write_json
from .validation import ValidationError, validate_bundle, validate_methodology


def _repository_root(value: str | None) -> Path:
    return (
        Path(value).expanduser().resolve()
        if value
        else Path(__file__).resolve().parents[1]
    )


def _emit(value: Any) -> None:
    sys.stdout.buffer.write(canonical_json_bytes(value))


def _load_inputs(args: argparse.Namespace) -> tuple[Any, Any, Path]:
    root = _repository_root(args.repository_root)
    bundle = load_json(args.bundle)
    methodology = load_json(args.method)
    return bundle, methodology, root


def command_validate(args: argparse.Namespace) -> int:
    bundle, methodology, root = _load_inputs(args)
    report = validate_bundle(bundle, methodology, repository_root=root)
    if args.output:
        write_json(args.output, report)
    _emit(report)
    return 0 if report["valid"] else 1


def command_validate_method(args: argparse.Namespace) -> int:
    root = _repository_root(args.repository_root)
    methodology = load_json(args.method)
    report = validate_methodology(methodology, repository_root=root)
    if args.output:
        write_json(args.output, report)
    _emit(report)
    return 0 if report["valid"] else 1


def _validated_calculation(
    args: argparse.Namespace,
) -> tuple[Any, Any, Path, dict[str, Any], dict[str, Any]]:
    bundle, methodology, root = _load_inputs(args)
    report = validate_bundle(bundle, methodology, repository_root=root)
    if not report["valid"]:
        raise ValidationError(report)
    calculation = calculate_index(
        bundle,
        methodology,
        evidence_mode=args.evidence_mode,
        capability_threshold=args.capability_threshold,
    )
    return bundle, methodology, root, report, calculation


def command_calculate(args: argparse.Namespace) -> int:
    _, _, _, report, calculation = _validated_calculation(args)
    write_json(args.output, calculation)
    _emit(
        {
            "calculation_output": str(Path(args.output).resolve()),
            "calculation_status": calculation.get("status"),
            "validation_sha256": report["document_sha256"],
            "latest": calculation.get("weeks", [])[-1]
            if calculation.get("weeks")
            else None,
        }
    )
    return 0


def command_export(args: argparse.Namespace) -> int:
    bundle, methodology, root, report, calculation = _validated_calculation(args)
    summary = export_release(
        bundle,
        methodology,
        calculation,
        args.output_dir,
        repository_root=root,
        validation_report=report,
    )
    _emit(summary)
    return 0


def command_reproduce(args: argparse.Namespace) -> int:
    root = _repository_root(args.repository_root)
    report = reproduce_release(args.release_dir, repository_root=root)
    if args.output:
        write_json(args.output, report)
    _emit(report)
    return 0 if report["reproduced"] else 1


def command_init_db(args: argparse.Namespace) -> int:
    connection = init_database(args.database)
    try:
        _emit(
            {
                "database": str(Path(args.database).resolve()),
                "initialized": True,
                "append_only": True,
            }
        )
    finally:
        if hasattr(connection, "close"):
            connection.close()
    return 0


def command_ingest(args: argparse.Namespace) -> int:
    root = _repository_root(args.repository_root)
    bundle = load_json(args.bundle)
    methodology = load_json(args.method)
    report = validate_bundle(bundle, methodology, repository_root=root)
    if not report["valid"]:
        raise ValidationError(report)
    connection = init_database(args.database)
    try:
        ingest_summary = ingest_bundle(connection, bundle)
    finally:
        if hasattr(connection, "close"):
            connection.close()
    _emit(
        {
            "database": str(Path(args.database).resolve()),
            "ingested": True,
            "summary": ingest_summary
            or {
                key: len(bundle.get(key, []))
                for key in (
                    "weeks",
                    "providers",
                    "creators",
                    "models",
                    "endpoints",
                    "source_artifacts",
                    "capability_evidence",
                    "token_counts",
                    "price_observations",
                    "corrections",
                )
            },
        }
    )
    return 0


def command_dump(args: argparse.Namespace) -> int:
    connection = init_database(args.database)
    try:
        bundle = dump_bundle(connection)
    finally:
        if hasattr(connection, "close"):
            connection.close()
    write_json(args.output, bundle)
    _emit(
        {
            "database": str(Path(args.database).resolve()),
            "bundle_output": str(Path(args.output).resolve()),
            "dataset_id": bundle.get("dataset_id"),
        }
    )
    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="python3 -m kapi",
        description="Isolated zero-spend KAPI-SW research prototype",
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    def add_root(target: argparse.ArgumentParser) -> None:
        target.add_argument(
            "--repository-root",
            help="repository root used to resolve frozen payload paths",
        )

    def add_inputs(target: argparse.ArgumentParser) -> None:
        target.add_argument("--bundle", required=True, help="normalized bundle JSON")
        target.add_argument("--method", required=True, help="methodology JSON")
        add_root(target)

    validate = subparsers.add_parser("validate", help="validate method and bundle")
    add_inputs(validate)
    validate.add_argument("--output", help="optional validation report JSON")
    validate.set_defaults(handler=command_validate)

    validate_method = subparsers.add_parser(
        "validate-method", help="validate methodology and payload hashes"
    )
    validate_method.add_argument("--method", required=True)
    validate_method.add_argument("--output")
    add_root(validate_method)
    validate_method.set_defaults(handler=command_validate_method)

    calculate = subparsers.add_parser(
        "calculate", help="calculate deterministic weekly prototype results"
    )
    add_inputs(calculate)
    calculate.add_argument("--output", required=True)
    calculate.add_argument(
        "--evidence-mode", choices=("official", "research"), default="official"
    )
    calculate.add_argument("--capability-threshold")
    calculate.set_defaults(handler=command_calculate)

    export = subparsers.add_parser(
        "export", help="calculate and export a frozen release bundle"
    )
    add_inputs(export)
    export.add_argument("--output-dir", required=True)
    export.add_argument(
        "--evidence-mode", choices=("official", "research"), default="official"
    )
    export.add_argument("--capability-threshold")
    export.set_defaults(handler=command_export)

    reproduce = subparsers.add_parser(
        "reproduce", help="verify hashes and independently recalculate a release"
    )
    reproduce.add_argument("--release-dir", required=True)
    reproduce.add_argument("--output")
    add_root(reproduce)
    reproduce.set_defaults(handler=command_reproduce)

    init_db = subparsers.add_parser(
        "init-db", help="initialize append-only SQLite storage"
    )
    init_db.add_argument("--database", required=True)
    init_db.set_defaults(handler=command_init_db)

    ingest = subparsers.add_parser(
        "ingest", help="validate and append a bundle to SQLite"
    )
    ingest.add_argument("--database", required=True)
    add_inputs(ingest)
    ingest.set_defaults(handler=command_ingest)

    dump = subparsers.add_parser(
        "dump", help="dump the current canonical dataset from SQLite"
    )
    dump.add_argument("--database", required=True)
    dump.add_argument("--output", required=True)
    dump.set_defaults(handler=command_dump)

    return parser


def main(argv: Sequence[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    try:
        return int(args.handler(args))
    except (ValidationError, CalculationError, ExportError, StoreError) as error:
        payload: dict[str, Any] = {
            "error": error.__class__.__name__,
            "message": str(error),
        }
        if isinstance(error, ValidationError):
            payload["validation"] = error.report
        sys.stderr.buffer.write(canonical_json_bytes(payload))
        return 2
    except (OSError, ValueError, KeyError) as error:
        sys.stderr.buffer.write(
            canonical_json_bytes(
                {"error": error.__class__.__name__, "message": str(error)}
            )
        )
        return 2


if __name__ == "__main__":
    raise SystemExit(main())
