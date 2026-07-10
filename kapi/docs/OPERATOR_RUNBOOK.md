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

2. Validate methodology and payload hashes:

       python3 -m kapi validate-method \
         --method kapi/config/methodology-v0.2.1.json

3. Validate the normalized bundle:

       python3 -m kapi validate \
         --bundle kapi/fixtures/synthetic-hand-example-v1.json \
         --method kapi/config/methodology-v0.2.1.json

4. Run all tests:

       python3 -m unittest discover -s kapi/tests -v

5. Export the synthetic release:

       python3 -m kapi export \
         --bundle kapi/fixtures/synthetic-hand-example-v1.json \
         --method kapi/config/methodology-v0.2.1.json \
         --output-dir kapi/outputs/sample-release-v0.2.1

6. Reproduce it:

       python3 -m kapi reproduce \
         --release-dir kapi/outputs/sample-release-v0.2.1

7. Confirm the release says not_for_publication and that the hand example is
   withheld for concentration while retaining its diagnostics.

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
| OpenAI dated id remains unverified | Keep candidate blocked; do not substitute the undated family id |
| Anthropic thinking cannot be omitted | Stop; do not send an explicit disabled value without official support |
| Any possible charge | Stop before the action and request separate approval |

## Recovery

Inputs and prior outputs are immutable. Generate a new bundle/release ID rather
than editing a released artifact. Restore from the frozen input copies, rerun
validation/calculation/export, independently reproduce, and retain both
vintages.

## Explicit stop

This runbook ends at a local synthetic prototype. Do not begin weekly shadow
collection, deploy, publish, or connect to WordPress.
