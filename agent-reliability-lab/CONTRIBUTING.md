# Contributing

Changes should keep every lab small, offline, mock-only, deterministic, and runnable with Node.js 22 using only built-in modules.

Before proposing a change:

1. Add or update a synthetic failure fixture.
2. Keep the control at one clearly named enforcement point.
3. Add a deterministic assertion and an inspectable artifact.
4. Update the README, architecture, threat model, runbook, and limitation statement when their boundary changes.
5. Run `npm test` from this directory.

Do not include credentials, live provider traffic, production records, customer data, copied proprietary material, or a claim that extends beyond the fixture evidence.
