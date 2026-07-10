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
- calculation independently reproduces;
- release status is publishable;
- evidence and coverage are complete;
- governance sign-offs exist;
- methodology/data vintages are explicit.

The adapter should create/update only a dedicated KAPI presentation record. It
must not rewrite historical release artifacts or source observations.

## Proposed phases

1. Read-only staging adapter consuming a synthetic release.
2. Staging page/API rendering with no production access.
3. Independent security, data, accessibility, and editorial review.
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
