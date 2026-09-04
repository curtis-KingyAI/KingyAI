import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext } from '../src/context.js';
import { writeDecisionReport } from '../src/report.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixtures = resolve(root, 'fixtures');
const policy = JSON.parse(await readFile(resolve(fixtures, 'policy.json')));
const grantFixture = JSON.parse(await readFile(resolve(fixtures, 'approval-grants.json')));
const launchFixture = JSON.parse(await readFile(resolve(fixtures, 'launch-request.json')));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await cp(resolve(fixtures, 'policy.json'), resolve(artifacts, 'policy.json'));
await cp(resolve(fixtures, 'approval-grants.json'), resolve(artifacts, 'approval-grants.json'));

const { gateway, clock, services, grantStore } = createContext({
  policy,
  grants: grantFixture.grants,
  auditPath: resolve(artifacts, 'audit.jsonl'),
  now: '2026-09-03T12:00:00.000Z'
});
const request = (correlationId, action, resource, approvalGrantId) => ({
  correlationId,
  actor: launchFixture.actor,
  action,
  resource,
  approvalGrantId,
  prompt: launchFixture.prompt
});

const steps = [];
async function run(name, action, resource, approvalGrantId) {
  const result = await gateway.execute(request(`demo-${String(steps.length + 1).padStart(3, '0')}`, action, resource, approvalGrantId));
  steps.push({ name, decision: result.decision, reasonCode: result.reasonCode });
  return result;
}

await run('collect an official source', 'source.collect', launchFixture.launch.source);
await run('create a draft', 'cms.draft.create', 'cms://drafts/launch-aurora');
await run('direct publish from hostile prompt', 'cms.publish', 'cms://records/launch-aurora');
await run('read unrelated private fixture', 'file.read', 'file://fixtures/private/operations-notes.txt');
await run('publish with one-use approval', 'cms.publish', 'cms://records/launch-aurora', 'approval-publish-aurora');
await run('replay the consumed approval', 'cms.publish', 'cms://records/launch-aurora', 'approval-publish-aurora');
await run('substitute another target', 'cms.publish', 'cms://records/launch-borealis', 'approval-publish-aurora-second');
clock.set('2026-09-03T13:00:00.000Z');
await run('use expired approval', 'cms.publish', 'cms://records/launch-aurora', 'approval-publish-expired');

await writeDecisionReport(resolve(artifacts, 'decision-report.md'), steps);
await writeFile(resolve(artifacts, 'cms-state.json'), `${JSON.stringify(services.snapshot(), null, 2)}\n`);
await writeFile(resolve(artifacts, 'approval-state.json'), `${JSON.stringify(grantStore.snapshot(), null, 2)}\n`);

const assertions = [
  ['source-and-draft.allowed', steps[0].decision === 'allow' && steps[1].decision === 'allow'],
  ['publish.requires-approval', steps[2].reasonCode === 'APPROVAL_REQUIRED'],
  ['unrelated-file.denied', steps[3].reasonCode === 'PERMISSION_DENIED'],
  ['scoped-approval.allows-once', steps[4].decision === 'allow' && steps[5].reasonCode === 'APPROVAL_REPLAYED'],
  ['target-substitution.denied', steps[6].reasonCode === 'APPROVAL_SCOPE_MISMATCH'],
  ['expired-approval.denied', steps[7].reasonCode === 'APPROVAL_EXPIRED']
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'agent-permissions-gate',
  labVersion: '0.1.0',
  fixtureId: launchFixture.fixtureId,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/policy.json', 'artifacts/approval-grants.json', 'artifacts/audit.jsonl', 'artifacts/decision-report.md'],
  limitations: ['Mock CMS only; this is not an authorization service audit.', 'Fixture grants are not cryptographically signed credentials.']
});
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

console.table(steps);
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'run-summary.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
