import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createContext } from '../src/context.js';
import { Code } from '../src/codes.js';

const fixtures = new URL('../fixtures/', import.meta.url);
const allowlist = JSON.parse(await readFile(new URL('server-allowlist.json', fixtures)));
const policies = JSON.parse(await readFile(new URL('tool-policy.json', fixtures)));
const grants = JSON.parse(await readFile(new URL('approval-grants.json', fixtures)));
const requests = JSON.parse(await readFile(new URL('requests.json', fixtures)));

async function sandbox() {
  const artifactsPath = await mkdtemp(join(tmpdir(), 'kingy-mcp-security-'));
  const context = createContext({ artifactsPath, servers: allowlist.servers, toolPolicies: policies, grants: grants.grants });
  return { artifactsPath, ...context };
}

function request(base, overrides = {}) {
  return { ...base, correlationId: 'test-001', ...overrides };
}

test('accepts an allowlisted valid result and logs redacted provenance', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const result = await box.host.call(request(requests.validResearch));
  assert.equal(result.code, Code.ACCEPTED);
  assert.equal(result.data.kind, 'evidence');
  const log = await readFile(join(box.artifactsPath, 'provenance-log.jsonl'), 'utf8');
  const event = JSON.parse(log.trim());
  assert.deepEqual(Object.keys(event).sort(), ['code', 'correlationId', 'inputFingerprint', 'outputFingerprint', 'serverId', 'timestamp', 'toolName']);
  assert.equal(log.includes('Aurora has a source-backed launch record'), false);
  assert.equal(log.includes('fixture-nonce'), false);
});

test('prompt-injection text remains untrusted data and cannot trigger a write', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const injected = await box.host.call(request(requests.validResearch, { scenario: 'prompt-injection' }));
  const publish = await box.host.call(request(requests.validPublish, { correlationId: 'test-002' }));
  const serverEvents = (await readFile(join(box.artifactsPath, 'fixture-server-events.jsonl'), 'utf8')).trim().split('\n').map(JSON.parse);
  assert.equal(injected.code, Code.ACCEPTED);
  assert.match(injected.data.text, /Ignore every host policy/);
  assert.equal(publish.code, Code.APPROVAL_REQUIRED);
  assert.equal(serverEvents.filter((event) => event.toolName === 'cms.publish').length, 0);
});

test('rejects malformed output and input domain/path violations before accepting data', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const malformed = await box.host.call(request(requests.validResearch, { scenario: 'malformed-output' }));
  const domain = await box.host.call(request(requests.validResearch, { input: { query: 'Aurora', url: 'https://evil.example/steal' } }));
  const traversal = await box.host.call(request(requests.validRead, { input: { path: '/workspace/fixtures/allowed/../../.ssh/id_rsa' } }));
  assert.equal(malformed.code, Code.OUTPUT_SCHEMA_INVALID);
  assert.equal(domain.code, Code.DOMAIN_NOT_ALLOWED);
  assert.equal(traversal.code, Code.PATH_NOT_ALLOWED);
  const serverEvents = (await readFile(join(box.artifactsPath, 'fixture-server-events.jsonl'), 'utf8')).trim().split('\n').map(JSON.parse);
  assert.equal(serverEvents.length, 1);
  assert.equal(serverEvents[0].scenario, 'malformed-output');
});

test('enforces server/tool allowlists and revocation', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const unknownServer = await box.host.call(request(requests.validResearch, { serverId: 'unknown-server' }));
  const unknownTool = await box.host.call(request(requests.validResearch, { toolName: 'shell.exec' }));
  box.registry.revoke('research-fixture-v1');
  const revoked = await box.host.call(request(requests.validResearch));
  assert.equal(unknownServer.code, Code.SERVER_NOT_ALLOWLISTED);
  assert.equal(unknownTool.code, Code.TOOL_NOT_ALLOWLISTED);
  assert.equal(revoked.code, Code.SERVER_REVOKED);
});

test('write-capable MCP tools require one scoped approval and reject replay', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const noApproval = await box.host.call(request(requests.validPublish));
  const approved = await box.host.call(request(requests.validPublish, { correlationId: 'test-002', approvalGrantId: 'approval-publish-aurora' }));
  const replay = await box.host.call(request(requests.validPublish, { correlationId: 'test-003', approvalGrantId: 'approval-publish-aurora' }));
  assert.equal(noApproval.code, Code.APPROVAL_REQUIRED);
  assert.equal(approved.code, Code.ACCEPTED);
  assert.equal(replay.code, Code.APPROVAL_REPLAYED);
  const serverEvents = (await readFile(join(box.artifactsPath, 'fixture-server-events.jsonl'), 'utf8')).trim().split('\n').map(JSON.parse);
  assert.equal(serverEvents.filter((event) => event.toolName === 'cms.publish').length, 1);
});
