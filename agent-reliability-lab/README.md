# Kingy Agent Reliability Lab

Six small, runnable Node.js laboratories for the controls that make agent work bounded, recoverable, repeatable, inspectable, and finite.

Every lab is offline, mock-only, deterministic, dependency-free, and designed for Node.js 22. Each reproduces one operational failure, applies one narrow control, and writes inspectable evidence.

> **Evidence boundary:** these repositories demonstrate local control behavior under synthetic fixtures. They are not production frameworks, security certifications, compliance claims, MCP protocol-conformance tests, or evidence about a particular model or vendor.

## Run the complete collection

```sh
git clone https://github.com/curtis-KingyAI/KingyAI.git
cd KingyAI/agent-reliability-lab
nvm use
npm test
```

`npm test` validates the hub, then runs all six test suites and demonstrations. No keys, accounts, network calls, packages, CMS, or production data are required.

## Choose a failure to control

| Lab | The production question | Runnable proof |
| --- | --- | --- |
| [Permissions gate](agent-permissions-gate/) | What may the agent do? | One scoped, one-use grant permits one mocked publication; direct, replayed, expired, and substituted actions are denied. |
| [Idempotent actions](idempotent-agent-actions/) | Can retries duplicate side effects? | Ten attempts around an ambiguous timeout yield exactly one mocked publication. |
| [Checkpoint recovery](agent-checkpoint-recovery/) | Where should a crashed workflow resume? | A forced crash after every stage reuses all verified upstream work. |
| [Cost circuit breaker](agent-cost-circuit-breaker/) | Can a loop overrun its budget? | Warning, degradation, checkpointed hard stop, resume continuity, and no-progress termination are visible. |
| [MCP security](mcp-security-lab/) | Can tool output expand host authority? | Host allowlists, schemas, path/domain restrictions, revocation, redaction, and independent write approval are exercised. |
| [Editorial evals](agent-editorial-evals/) | Can unsafe drafts reach review? | Frozen cases stop unsupported claims, false citations, stale pricing, missing limits, and missed escalations. |

## What passed in release 0.1.0

- 6 runnable labs
- 26 deterministic tests
- 6 protected demo outcomes
- Node.js 22 CI
- 0 runtime dependencies

The metrics describe this release and its included fixtures, tested on 2026-09-03. Read each lab's `artifacts/run-summary.json` for assertions and limitations.

## Hub documentation

- [Cross-lab quickstart](kingy-agent-reliability-lab/QUICKSTART.md)
- [Control matrix](kingy-agent-reliability-lab/CONTROL-MATRIX.md)
- [Evidence methodology](kingy-agent-reliability-lab/EVIDENCE-METHODOLOGY.md)
- [Editorial launch outline](kingy-agent-reliability-lab/EDITORIAL-LAUNCH.md)

## License and security

Code is Apache-2.0; documentation and diagrams are CC-BY-4.0. The labs use synthetic data and intentionally make no live calls. Please do not publish credentials, real customer data, production policy, or exploit payloads in public issues; use Kingy's contact route for sensitive reports.

Public client guide: [Kingy Agent Reliability Lab](https://kingy.ai/apps-labs/agent-reliability-lab/)
