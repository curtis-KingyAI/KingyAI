# KAPI governance policy v1.0.0

Status: current forward-only policy vintage. Historical methodology and sample
release vintages through v0.2.2 remain byte-immutable, but their legacy free-
text review, signoff, or readiness fields are not authorization sources for
this policy.

## Decision

KAPI uses a staged-hybrid target model:

1. a named external reviewer reviews each exact methodology version once;
2. routine releases under that methodology remain explicitly
   operator-reviewed and never imply release-level external review;
3. only an exact release separately reviewed and recalculated may carry a
   scoped external-release statement; and
4. an appointed publication authorizer remains a separate role.

This is a real named-reviewer process, not a relabeling exercise. If Kingy.ai
does not fund and operate that process, the permanent ceiling is a truthful
operator-review label after trusted operator authentication exists; no external
methodology assurance may be implied.

The current implementation cannot activate that target. SQLite file ownership
and a Python `ContextVar` are not identity authentication, so they cannot even
prove that a named operator reviewed a specific release. The standard-library
prototype also does not cryptographically verify reviewer signatures.
Accordingly, schema v2 starts releases as `unreviewed` and has no edge to
`operator_reviewed`; it also records `trusted_verifier_gate=failed` under a
constraint that caller input cannot change. No operator-reviewed,
external-methodology, exact-release, `ready`, or `publication_eligible=true`
result is exportable in policy v1.0.0. The only current publication-candidate
label is:

> Governance status: Unreviewed draft. Automated validation completed for this
> artifact; no operator or external methodology review is complete.

The agreed operator-reviewed label remains a future governed state. Activating
it requires a new policy/schema vintage and a trusted identity adapter that
binds a named operator, exact release, artifact set, time, and review event.
Neither a caller-supplied record nor a local actor context may perform that
upgrade.

Policy v1.0.0 also prohibits the generic legacy assurance adjective in current
machine-readable artifacts and publication-candidate labels. A later policy
may use that word only after the named-reviewer standard below is met and only
with a scope that
states whether the methodology or this exact release was reviewed. The
preferred publication-candidate wording remains the more precise `external
methodology review` or `named external release review`.

## Named-reviewer activation standard

Before any external-assurance claim can be activated, one immutable review
record must establish all of the following:

- the reviewer's legal/full name, affiliation, qualifications, stable
  registrar-verified identity, appointed role, and the exact append-only
  registry event containing the registered public key;
- relationship, compensation, and conflict disclosures, with no operator,
  methodology-owner, release-authorizer, publication-authorizer, self-
  registrar, or self-appointer role overlap;
- the exact approved scope and an outcome, findings, unresolved-issues list,
  validity interval, evidence record, and append-only revocation path;
- for methodology review: exact methodology ID, version, document SHA-256,
  implementation commit, and review-artifact manifest SHA-256;
- for an exact-release claim: all methodology bindings plus the stable release
  ID, exact frozen release-artifact membership SHA-256, and equality between the
  release code commit and methodology-gate implementation commit;
- a signature over the canonical payload and a separately authenticated
  verifier attestation from a principal who holds no authoring, review,
  operator, or publication role; and
- public attribution that exposes the stable identity and appointment evidence,
  bound and latest registry events (including revocation), qualifications and
  key evidence, all disclosures, date, scope, methodology version, result,
  findings, unresolved issues, signature/attestation evidence, and whether the
  exact release itself was reviewed.

A methodology, implementation commit, evidence policy, selection rule,
validation rule, public claim, reviewer identity/key, scope, or unresolved-
issue change expires the prior methodology assurance and requires a new review
record. A release correction or artifact-membership change creates a new
release ID/binding and cannot inherit an exact-release claim.

## Forward schema

The append-only schema separately records stable controller identity evidence,
full name, affiliation, registrar/appointer, qualifications, conflict,
relationship and compensation disclosures, exact methodology/version/hash,
implementation commit and review-artifact manifest, exact release/artifact
membership, findings, unresolved issues, evidence record, reviewer key
metadata, signature bytes hash, and an untrusted local verification claim.
Initial registration, qualification/key rotation, and revocation are immutable
registry events. Each review binds the event current at review time; signature
claims and every future claim-bearing transition recheck that the same event is
still the latest active, unexpired state. Historical records are never rewritten.
Those fields support tests and a later migration; their presence is not
signature verification or external assurance.

Release identity is derived from frozen dataset/methodology hashes and the
mathematical calculation content, not mutable governance prose. Methodology,
snapshot, calculation, and release child membership freezes before or at its
governed parent binding. A late insert therefore cannot silently change the
reviewed object while leaving the stored digest unchanged.

The v0.3.0 bundle is an exact closed object schema: every object path has an
explicit allowed key set, and every container/leaf path has an exact type and
scalar rule. Public strings are exact safe values, closed enums,
hashes/timestamps/references, or narrow machine identifiers. Caller
`expected_result` oracles and `generation` spend/provider metadata are absent
and forbidden. An undeclared field, wrong type, unclassified string, or
non-ASCII value fails even when its content appears neutral. A future field,
prose carrier, character set, or grammar requires a new bundle-schema vintage.
The canonical object hash must additionally equal the deterministic generated
fixture, rejecting in-grammar identity swaps and hash-repaired source
contradictions. Dynamic or observed input requires a new reviewed vintage.

The forward-input boundary recursively normalizes every key and string with
NFKD, case folding, combining-mark removal, and common-confusable folding. The
current bundle schema has no claim-key allowlist entries: any key containing a
review, approval, assurance, audit, verification, signature, authorization,
publication, deployment, launch/readiness, operator, accreditation/
certification, attestation, endorsement, governance, or legacy independence-
assurance stem is rejected at any depth. Neutral keys do not
create an escape hatch because assertion-like strings, including go-live
synonyms, are scanned recursively too. The closed scalar grammar—not a semantic
phrase allowlist—is the primary forward-input boundary. To prevent
split carriers, each non-root record/object is also evaluated from all of its
recursively nested key/string fragments without combining unrelated array
records. Subject-bearing string lists are evaluated as containers, including
mixed string/object lists. Percent, HTML-entity, and literal-codepoint decoding
is capped at four rounds; residual encoding syntax is rejected.

The export boundary runs that bundle scan again before rendering any frozen
input bytes, then separately recomputes validation and the full calculation
from frozen inputs and requires canonical equality. A caller cannot inject a
synonym such as `human_review_status`, a bare `reviewer`, `auditor`, or
`signatory` field, or claim prose through a nested week, source note, validation
report, methodology, or undeclared bundle field and have it copied into a
release.

The lifecycle boundary accepts only the exact empty caller-diagnostics object;
all persisted diagnostic keys are lifecycle-owned. A normalized recursive
content check rejects claim language in nested strings as defense in depth,
and base-week states are controlled enum values. Policy v1.0.0 rejects every
caller-supplied implementation-isolated recalculation report and records only
`not_supplied`. Even exact-schema `pass` output is not proof that a checker ran.
A future reviewed path must recompute internally from the exact full frozen
calculation and hash-bind the calculation and result; the standalone checker is
non-authorizing.

The database separately enforces the stored form. `init_database` registers
an exact canonical policy-v1 diagnostic validator on each initialized SQLite
connection. A `BEFORE INSERT` trigger rejects a calculation unless the document
has only the lifecycle-owned keys and exact values, the controlled release/base
states, the fixed `not_supplied` secondary object, and a disposition consistent
with the calculation status. The initial release-binding trigger runs the same
validation again against the stored calculation. A missing or unregistered
validator therefore prevents both calculation insertion and governance binding;
the binding's own disposition must also match the stored calculation status,
and actor binding does not bypass either check.

The forward migration deliberately does not rewrite or backfill calculations
that already existed under schema v1. Inferring an unreviewed label or any later
review state would alter the historical record without evidence. Existing rows
remain readable byte-for-byte, but a nonconforming row cannot receive a policy-
v1 governance binding because the binding trigger revalidates it. Every new
calculation inserted after migration is subject to the insert guard.

## Required migration to activate external claims

A later reviewed migration must provide all of the following before changing
the hard-failed gate:

- a trusted identity adapter outside the caller-controlled JSON/SQLite process
  for both operator and external-review events;
- registrar-verified stable natural-person/controller identities and pinned
  public keys;
- cryptographic verification of the canonical signed review payload;
- a separately authenticated verifier attestation distinct from the
  reviewer and Kingy.ai authorizers;
- exact methodology and, when claimed, exact release binding;
- trusted off-process delivery of the structurally modeled revocation/expiry
  events and immutable audit evidence outside publisher control;
- adversarial tests for aliasing, self-registration, self-appointment, forged
  actors, wrong keys/signatures, stale commits/manifests, and direct SQL; and
- export/WordPress adapters that obtain governance status from the trusted
  ledger and render complete public attribution.

Changing a JSON flag or inserting a nonzero review ID is never sufficient.

SQLite triggers and Python callbacks enforce ordinary application paths but do
not defend against the owner of the database file, schema, host account, or
executable. Production authorization must place trusted identity, signature
verification, and durable audit evidence under separate administrative control
from the publisher.
