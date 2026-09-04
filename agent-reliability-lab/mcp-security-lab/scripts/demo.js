import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext } from '../src/context.js';
import { writeSecurityReport } from '../src/report.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixtureDirectory = resolve(root, 'fixtures');
const allowlist = JSON.parse(await readFile(resolve(fixtureDirectory, 'server-allowlist.json')));
const toolPolicies = JSON.parse(await readFile(resolve(fixtureDirectory, 'tool-policy.json')));
const grantFixture = JSON.parse(await readFile(resolve(fixtureDirectory, 'approval-grants.json')));
const requests = JSON.parse(await readFile(resolve(fixtureDirectory, 'requests.json')));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
for (const file of ['server-allowlist.json', 'tool-policy.json', 'approval-grants.json', 'requests.json']) {
  await cp(resolve(fixtureDirectory, file), resolve(artifacts, file));
}

const { host, registry } = createContext({ artifactsPath: artifacts, servers: allowlist.servers, toolPolicies, grants: grantFixture.grants });
let sequence = 0;
const steps = [];
async function run(name, request) {
  sequence += 1;
  const result = await host.call({ ...request, correlationId: `demo-${String(sequence).padStart(3, '0')}` });
  steps.push({ name, code: result.code, accepted: result.accepted });
  return result;
}

await run('valid research result', requests.validResearch);
await run('prompt injection stays evidence data', { ...requests.validResearch, scenario: 'prompt-injection' });
await run('malformed output is rejected', { ...requests.validResearch, scenario: 'malformed-output' });
await run('disallowed research domain', { ...requests.validResearch, input: { query: 'Aurora', url: 'https://evil.example/steal' } });
await run('path traversal attempt', { ...requests.validRead, input: { path: '/workspace/fixtures/allowed/../../.ssh/id_rsa' } });
await run('unallowlisted server', { ...requests.validResearch, serverId: 'unknown-server' });
await run('unallowlisted tool', { ...requests.validResearch, toolName: 'shell.exec' });
await run('publish without independent approval', requests.validPublish);
await run('publish with scoped approval', { ...requests.validPublish, approvalGrantId: 'approval-publish-aurora' });
await run('replay approval', { ...requests.validPublish, approvalGrantId: 'approval-publish-aurora' });
registry.revoke('research-fixture-v1');
await run('revoked server', requests.validResearch);

await writeSecurityReport(resolve(artifacts, 'security-decision-report.md'), steps);
const assertions = [
  ['allowlisted-valid-result.accepted', steps[0].accepted],
  ['prompt-injection.no-implicit-action', steps[1].accepted && steps[7].code === 'APPROVAL_REQUIRED'],
  ['malformed-output.rejected', steps[2].code === 'OUTPUT_SCHEMA_INVALID'],
  ['domain-and-path.restricted', steps[3].code === 'DOMAIN_NOT_ALLOWED' && steps[4].code === 'PATH_NOT_ALLOWED'],
  ['server-and-tool.allowlists-enforced', steps[5].code === 'SERVER_NOT_ALLOWLISTED' && steps[6].code === 'TOOL_NOT_ALLOWLISTED'],
  ['write.requires-one-use-approval', steps[8].accepted && steps[9].code === 'APPROVAL_REPLAYED'],
  ['revoked-server.denied', steps[10].code === 'SERVER_REVOKED']
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'mcp-security-lab',
  labVersion: '0.1.0',
  fixtureId: requests.fixtureId,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/server-allowlist.json', 'artifacts/tool-policy.json', 'artifacts/provenance-log.jsonl', 'artifacts/fixture-server-events.jsonl', 'artifacts/security-decision-report.md'],
  limitations: ['This is a local MCP-shaped fixture transport, not MCP protocol conformance testing.', 'No OS sandbox, network firewall, credential broker, or live MCP server was tested.']
});
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);
console.table(steps);
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'run-summary.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
