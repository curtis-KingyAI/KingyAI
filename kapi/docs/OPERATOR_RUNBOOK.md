# Prototype operator runbook

## Preconditions

- Work only in the isolated KAPI worktree.
- Confirm external spend and every paid limit remain $0.
- Do not load credentials or API keys.
- Do not use production data or WordPress write endpoints.
- Run from the repository root with the system Python 3 standard library.

## Standard local run

1. Regenerate and verify synthetic fixtures:

       python3 kapi/fixtures/build_synthetic.py --check

   Verify portable payload reproduction with no source-asset path:

       env -u KAPI_O200K_ASSET_PATH \
         python3 kapi/fixtures/build_payloads.py --check-frozen-manifest

2. Validate methodology and payload hashes:

       python3 -m kapi validate-method \
         --method kapi/config/methodology-v0.3.0.json

3. Validate the normalized bundle:

       python3 -m kapi validate \
         --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
         --method kapi/config/methodology-v0.3.0.json

4. Run all tests:

       python3 -m unittest discover -s kapi/tests -v

5. Export the synthetic release:

       python3 -m kapi export \
         --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
         --method kapi/config/methodology-v0.3.0.json \
         --output-dir kapi/outputs/sample-release-governance-v1.0.0

6. Reproduce it:

       python3 -m kapi reproduce \
         --release-dir kapi/outputs/sample-release-governance-v1.0.0

7. Confirm the release says not_for_publication and that the hand example is
   withheld for concentration while retaining its diagnostics.

The active calculate/export commands accept only the exact current v0.3.0
bundle/methodology pair shown above. Do not use current code to generate a
legacy release. Validate retained v0.2.x inputs only as hash-pinned historical
records and reproduce each old release from its corresponding pinned checkout.

## Storage exercise

Use a disposable database under kapi/.tmp. Initialize, ingest once, dump, and
compare the canonical bundle. Attempted UPDATE or DELETE operations must fail.

## Failure handling

| Failure | Required response |
|---|---|
| Validation error | Stop; fix the input or methodology; never bypass |
| Missing source/payload | Stop; do not impute an official value |
| Conflicting observation | Add an explicit superseding observation/correction |
| Incomplete provider triple | Withhold the affected profile and week |
| Concentration breach | Retain diagnostics; mark release withheld |
| Reproduction mismatch | Quarantine output and investigate hashes/code/config |
| Operator-review, external-review, or publication-ready request | Stop; policy v1.0.0 hard-fails these paths until trusted identity and cryptographic verification are implemented in a reviewed migration |
| Reviewer key, qualification, or validity changes | Append a registrar-authenticated supersession event; never update the initial registration or an earlier event |
| Reviewer credential revoked or suspected compromised | Append a revocation event immediately; stale reviews/signature claims cannot advance while the latest event is revoked |
| Any secondary recalculation object is supplied to lifecycle | Stop; policy v1.0.0 accepts absence only, and standalone checker output is not lifecycle evidence |
| Caller calculation diagnostics are nonempty | Stop; use lifecycle-owned structured fields or introduce a reviewed schema migration rather than storing free-form notes |
| Missing full tokenizer asset | Portable checks may continue; full proof must stop until an explicit approved path is supplied |
| Source asset or derived manifest mismatch | Stop; do not rewrite the frozen manifest or payloads |
| OpenAI dated id remains unverified | Keep candidate blocked; do not substitute the undated family id |
| Anthropic thinking cannot be omitted | Stop; do not send an explicit disabled value without official support |
| Any possible charge | Stop before the action and request separate approval |

## Recovery

Inputs and prior outputs are immutable. A data, methodology, or mathematical
correction creates a new content-derived release ID; a later governance event
must bind the existing content ID and exact artifact set rather than rewrite
them. Restore from frozen input copies, rerun validation/calculation/export,
run the implementation-isolated reproduction, and retain every vintage.

Do not use private local actor-binding helpers as authentication. The public CLI
intentionally exposes no governance mutation command.

## Explicit stop

This runbook ends at a local synthetic prototype. Do not begin weekly shadow
collection, deploy, publish, or connect to WordPress.
