# Proposed KALI integration

No integration described here has been applied to KALI or WordPress.

## Principle

KALI remains the editorial/model archive. KAPI calculations use a separate
append-only evidence and release domain. WordPress consumes a completed,
validated release artifact; it is never the calculation system of record.

## Future read-only inputs

After separate approval, KAPI could seed identity candidates from KALI:

- provider and model names;
- modality and family labels;
- official/pricing/source URLs;
- verification notes.

These remain candidates until normalized to exact creator, version, endpoint,
configuration, region, tier, and immutable source evidence. Existing free-text
pricing must never be silently converted into historical observations.

## Future write boundary

A later WordPress adapter should accept only a finalized release bundle whose:

- manifest and file hashes verify;
- a trusted internal path recomputes the implementation-isolated secondary
  check from the exact full calculation and hash-binds the result (not available
  in policy v1.0.0; caller-supplied checker output is rejected);
- calculation disposition is eligible;
- evidence and coverage are complete;
- a trusted governance ledger reports publication authorization;
- methodology/data vintages are explicit.

The adapter should create/update only a dedicated KAPI presentation record. It
must not rewrite historical release artifacts or source observations.

## Proposed phases

1. Read-only staging adapter consuming a synthetic release.
2. Staging page/API rendering with no production access.
3. Separately appointed security, data, accessibility, and editorial review.
4. Explicit authorization for production integration.
5. Publication only after the shadow/beta gates in the planning package.

## Required kingy.ai safeguards

Any eventual publication must:

- use verified TLS;
- preserve Koko Analytics;
- have one story-specific featured image;
- verify nonzero WordPress featured_media;
- avoid duplicating the hero image in the body;
- remain draft/scheduled if any image/publication gate fails.

The present prototype performs none of these actions because publication is out
of scope.

Policy v1.0.0 also blocks any adapter from treating local SQLite actor bindings,
review IDs, or structural signature fields as authentication. A future
WordPress adapter must fail closed unless a later trusted-verifier migration
supplies complete public attribution and an authorized ledger state.
Complete attribution means the exact review scope/subject, decision and
findings, disclosures, stable reviewer identity and appointment, qualifications,
bound and latest registry events (including revocation), signature material,
and verifier-attestation evidence. An `untrusted_local_claim` must be rendered as
such and can never be upgraded by presentation code.
