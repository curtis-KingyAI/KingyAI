# Evidence methodology

## Purpose

The Lab is designed to make a narrow operational claim observable. It does not ask readers to infer broad production reliability from a polished demo.

Every claim should answer four questions:

1. **What control is demonstrated?**
2. **What failure is intentionally injected?**
3. **What local artifact proves the stated behavior?**
4. **What does this experiment not prove?**

## Evidence classes

| Class | Meaning | Examples in the Lab |
| --- | --- | --- |
| Fixture-defined | A synthetic, versioned input selected for the experiment | Hostile prompt, timeout point, stale price snapshot, malformed tool response |
| Locally observed | A behavior produced by the runnable code on that fixture | Denial code, one publication event, reused checkpoint, hard budget stop |
| Generated trace | An inspectable record written by the lab | JSON Lines log, run summary, scorecard, decision report, review packet |
| Production translation | An explicitly limited explanation of what a live system would need | Database transaction, workload identity, provider billing reconciliation, sandbox |

Do not present a fixture-defined or locally observed result as a statement about an arbitrary model, vendor, real customer workflow, or production security posture.

## Common evidence contract

Each lab emits `artifacts/run-summary.json` with at least:

```json
{
  "lab": "lab-id",
  "labVersion": "0.1.0",
  "fixtureId": "versioned-fixture",
  "outcome": "protected",
  "assertions": [{"id": "control.assertion", "result": "pass"}],
  "evidence": ["artifacts/specific-log.jsonl"],
  "limitations": ["What this local test does not establish."]
}
```

An assertion is useful only when its evidence path exists and a reader can understand the preconditions. A green result does not erase the limitation field.

## Release gate

A lab release is eligible for public editorial packaging when all of the following are true:

- The unsafe fixture fails predictably, and the controlled path passes.
- Tests run from a clean checkout using the declared runtime.
- The demo is offline, mock-only, deterministic, and finishes quickly.
- Artifacts match the documentation and include no credentials, personal data, or live operational records.
- README, architecture, threat model, runbook, and limitations are reviewed.
- The article and video point to an exact release tag, fixture version, and test date.

The editorial-evals lab adds a stricter gate: every hard fixture must pass and every case must have an inspectable trace. Subjective quality assessment must remain separate from this gate.

## Updates and corrections

Version each material change to a fixture, dependency, policy, assertion, runtime, or output contract. Re-run tests and demos after the change. If a published article or video overstates what a lab proved, update the hub, repository, article, and video description with a correction that names the changed scope.

## Required labels for published coverage

- **Tested behavior:** state the exact observed local outcome.
- **Conditions:** state fixture, runtime, and control version.
- **Limit:** state the most material non-proof.
- **Translation:** distinguish the reference implementation from a production design.
- **Relationship/disclosure:** keep commercial relationships separate from technical findings.
