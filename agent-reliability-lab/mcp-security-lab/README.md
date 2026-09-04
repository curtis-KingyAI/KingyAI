# MCP Security Lab

A small, offline Kingy Agent Reliability Lab that demonstrates host-side boundaries for MCP-style tool calls.

## Control demonstrated

The host allowlists server identities and tool names, validates input/output schemas, constrains domains and paths, treats tool output as untrusted data, logs redacted provenance, supports revocation, and independently requires a one-use approval for write-capable tools.

## Failure injected

A local fixture server returns prompt-injection text and malformed output. The demo also requests an unallowlisted server/tool, a disallowed domain, path traversal, a write without approval, an approval replay, and a call after server revocation.

## Evidence produced

The demo writes `server-allowlist.json`, `tool-policy.json`, `provenance-log.jsonl`, `fixture-server-events.jsonl`, `security-decision-report.md`, and `run-summary.json` under `artifacts/`.

## Not proven

This lab uses an in-memory MCP-shaped fixture transport so it can remain fully offline and dependency-free. It is not a conformance test for the MCP protocol or SDK, an operating-system sandbox, a network firewall, a credential broker, or evidence that a real MCP server is trustworthy.

## Quickstart

Requires Node.js 22 LTS. No package installation, live MCP server, credential, model call, or network connection is required.

```sh
npm test
npm run demo
```

Expected protected behavior:

- `research-fixture-v1` and its named tools are the only accepted fixture identity/tool combinations.
- Valid evidence is accepted with provenance; prompt-injection text remains data, not a command.
- Malformed output, disallowed domains, and path traversal are rejected.
- A publish suggestion cannot bypass the host's separate one-use approval gate.
- Revocation blocks all later calls from the previously valid server.

Inspect `artifacts/run-summary.json`, `artifacts/security-decision-report.md`, and `artifacts/provenance-log.jsonl`.

```sh
npm run clean
```

The clean command removes generated files from this repository's `artifacts/` directory only.

## Design choices

- Host policy is enforced before the fixture server is invoked.
- Output is schema-validated after invocation and before it can reach downstream code.
- Provenance logs identities, reason codes, and fingerprints—not raw request text, response text, grant IDs, or grant nonces.
- The fixture server models untrusted output, not a trusted policy engine.
- A real MCP SDK is intentionally not bundled: it is not installed locally, and adding it would make the default offline lab dependency-dependent. Replace this fixture transport with an exact-version-pinned official SDK in an integration-specific follow-up, retaining the same host controls and tests.

## Repository map

```text
src/          host policy, schemas, constraints, fixture server, grants, provenance
fixtures/     allowlist, tool policy, scoped approval, and local requests
scripts/      deterministic hostile-input demo and artifact cleanup
test/         identity, schema, injection, path/domain, revocation, and write-gate tests
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting the pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
