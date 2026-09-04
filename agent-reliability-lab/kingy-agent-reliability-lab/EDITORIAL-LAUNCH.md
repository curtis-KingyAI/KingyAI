# Editorial launch outline

## Editorial positioning

**Series title:** Production Agent Runbooks

**Promise:** Six small repositories for the hard parts of production agents: authority, recovery, safe retries, tool security, measurable quality, and bounded cost.

**Audience:** AI builders, technical founders, platform/security teams, and buyers who need more than a feature demo before trusting an agent with meaningful work.

**Editorial distinction:** Each module reproduces one failure, applies a narrow control, and retains visible evidence. The series is not an endorsement of “fully autonomous” agents or a certification of any vendor/framework.

## Launch package

| Asset | Purpose | Required evidence |
| --- | --- | --- |
| Hub page | Entry point, chooser, method, and limits | Six live repository cards with release/test dates and limitation labels |
| Pillar article | Explain the shift from demo to bounded operation | Control matrix and links to all six source repositories |
| Six repository articles | Explain one failure/control pair each | Exact command, fixture, run summary, limitation, production translation |
| Six videos | Make the failure/fix/evidence visually legible | Unsafe run, protected rerun, artifact inspection, exact release tag |
| Newsletter/brief | Announce a module without overstating it | One observed finding, one limitation, one runnable link |
| Capstone later | Compose controls only after individual labs are stable | Re-run individual evidence plus its own stated integration limits |

## Publication sequence

### Module 0 — Pillar and hub

**Working headline:** *From agent demo to production workflow: six controls you can run locally.*

Introduce the shared mocked launch-record workflow and explain why a model’s fluent output is not an operational guarantee. Include the evidence methodology and a “choose your first control” navigator.

### Module 1 — Authority and safe retries

- **Article:** *Your agent should draft the post, not publish it: a runnable permission gate.*
- **Video cold open:** direct mocked publication denied; one scoped approval succeeds once.
- **Companion article:** *Retries should not create ten posts: one idempotency key, one result.*
- **Video cold open:** first client call times out after accepted publish; nine retries still yield one publication.

### Module 2 — Recovery and finite execution

- **Article:** *When an agent crashes halfway through research, where should it resume?*
- **Video cold open:** forced crash after draft; restart reuses verified work and starts at fact-check.
- **Companion article:** *The agent loop that keeps spending: a cost circuit breaker you can inspect.*
- **Video cold open:** fake spend reaches warning, degradation, checkpoint, and terminal stop.

### Module 3 — Tool trust and editorial handoff

- **Article:** *Treat MCP output as untrusted: a host-side security lab.*
- **Video cold open:** a tool response attempts prompt injection and path traversal; the host records data but denies the expanded authority.
- **Companion article:** *A fluent draft can still be unsafe: hard evals for editorial agents.*
- **Video cold open:** a weak candidate is blocked for a stale price and false citation; a constrained trace passes or escalates.

## Standard module structure

1. Show the operational failure in the first 20 seconds / first paragraph.
2. State the boundary: fixture, mock, runtime, and what is not touched.
3. Explain one control and one enforcement point.
4. Run unsafe then protected behavior.
5. Inspect the test and generated evidence—not just the final UI.
6. Translate to production requirements without claiming the lab already supplies them.
7. Link to exact source release, related module, methodology, and corrections path.

## Editorial safeguards

- Never describe the labs as a security certification, production deployment recipe, or proof of an individual vendor’s reliability.
- Separate local observed behavior from general explanation and commercial context.
- Use synthetic fixtures only in demonstrations; never show production credentials, customer data, private documents, real budgets, or live CMS actions.
- State source/test date, runtime, lab version, fixture ID, and limitation on every published module.
- Require a correction/update note if any release tag, fixture, or conclusion changes materially.

## Calls to action

Primary: **Run one failure locally.**

Secondary: **Inspect the evidence boundary before adapting the pattern.**

Avoid calls to action that imply users should grant an agent broad production authority or connect a live account to a teaching repository.
