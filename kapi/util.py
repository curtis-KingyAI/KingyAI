"""Deterministic serialization, hashing, and parsing helpers."""

from __future__ import annotations

import csv
import hashlib
import io
import json
import os
import tempfile
from datetime import datetime, timezone
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any, Iterable, Mapping, Sequence


PROTOTYPE_NOTICE = "SYNTHETIC KAPI PROTOTYPE — NOT AN OFFICIAL OR PUBLISHED INDEX"
PROTOTYPE_CITATION_TEXT = (
    "Prototype diagnostic only; do not cite as a Kingy AI Price Index release."
)
SHADOW_NOTICE = "UNPUBLISHED KAPI SHADOW — NOT AN OFFICIAL OR PUBLIC INDEX"
SHADOW_CITATION_TEXT = (
    "Shadow-operation diagnostic only; do not cite as a Kingy AI Price Index release."
)


def artifact_notice(dataset_kind: Any) -> tuple[str, str]:
    """Return fail-closed notice and citation text for an artifact's data kind."""

    if dataset_kind == "observed":
        return SHADOW_NOTICE, SHADOW_CITATION_TEXT
    return PROTOTYPE_NOTICE, PROTOTYPE_CITATION_TEXT


def canonical_json_text(value: Any) -> str:
    """Return the prototype's canonical JSON representation."""

    return (
        json.dumps(
            value,
            ensure_ascii=False,
            sort_keys=True,
            separators=(",", ":"),
            allow_nan=False,
        )
        + "\n"
    )


def canonical_json_bytes(value: Any) -> bytes:
    return canonical_json_text(value).encode("utf-8")


def load_json(path: str | os.PathLike[str]) -> Any:
    with Path(path).open("r", encoding="utf-8") as handle:
        return json.load(handle)


def sha256_bytes(value: bytes) -> str:
    return hashlib.sha256(value).hexdigest()


def sha256_file(path: str | os.PathLike[str]) -> str:
    digest = hashlib.sha256()
    with Path(path).open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def atomic_write(path: str | os.PathLike[str], data: bytes) -> None:
    destination = Path(path)
    destination.parent.mkdir(parents=True, exist_ok=True)
    descriptor, temporary_name = tempfile.mkstemp(
        dir=destination.parent, prefix=f".{destination.name}.", suffix=".tmp"
    )
    try:
        with os.fdopen(descriptor, "wb") as handle:
            handle.write(data)
            handle.flush()
            os.fsync(handle.fileno())
        os.replace(temporary_name, destination)
    finally:
        try:
            os.unlink(temporary_name)
        except FileNotFoundError:
            pass


def write_json(path: str | os.PathLike[str], value: Any) -> None:
    atomic_write(path, canonical_json_bytes(value))


def csv_bytes(
    rows: Iterable[Mapping[str, Any]], fieldnames: Sequence[str]
) -> bytes:
    buffer = io.StringIO(newline="")
    writer = csv.DictWriter(
        buffer,
        fieldnames=list(fieldnames),
        extrasaction="raise",
        lineterminator="\n",
    )
    writer.writeheader()
    for row in rows:
        writer.writerow(
            {
                key: "" if row.get(key) is None else str(row.get(key))
                for key in fieldnames
            }
        )
    return buffer.getvalue().encode("utf-8")


def parse_decimal(value: Any, *, field: str = "value") -> Decimal:
    if not isinstance(value, str):
        raise ValueError(f"{field} must be an exact decimal string")
    try:
        parsed = Decimal(str(value))
    except (InvalidOperation, ValueError) as error:
        raise ValueError(f"{field} is not a valid decimal: {value!r}") from error
    if not parsed.is_finite():
        raise ValueError(f"{field} must be finite")
    return parsed


def decimal_text(value: Decimal, *, places: int | None = None) -> str:
    if places is not None:
        quantum = Decimal(1).scaleb(-places)
        value = value.quantize(quantum)
    text = format(value, "f")
    if "." in text:
        text = text.rstrip("0").rstrip(".")
    return text or "0"


def parse_utc(value: str, *, field: str = "timestamp") -> datetime:
    if not isinstance(value, str) or not value.endswith("Z"):
        raise ValueError(f"{field} must be an ISO-8601 UTC timestamp ending in Z")
    try:
        parsed = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError as error:
        raise ValueError(f"{field} is not a valid ISO-8601 timestamp") from error
    if parsed.tzinfo != timezone.utc:
        parsed = parsed.astimezone(timezone.utc)
    return parsed


def rational_decimal(value: Mapping[str, Any], *, field: str) -> Decimal:
    try:
        numerator = int(value["numerator"])
        denominator = int(value["denominator"])
    except (KeyError, TypeError, ValueError) as error:
        raise ValueError(
            f"{field} must contain integer numerator and denominator"
        ) from error
    if denominator <= 0 or numerator < 0:
        raise ValueError(f"{field} must be a nonnegative rational")
    return Decimal(numerator) / Decimal(denominator)
