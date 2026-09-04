import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createContext } from '../src/context.js';

const fixture = JSON.parse(await readFile(new URL('../fixtures/scenarios.json', import.meta.url)));

async function sandbox() {
  return mkdtemp(join(tmpdir(), 'kingy-cost-governor-'));
}

test('warning, degradation, checkpointed hard stop, and post-cap call blocking are enforced', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  const first = await createContext({ artifactsPath }).workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'hard' });
  assert.equal(first.status, 'BUDGET_EXHAUSTED');
  assert.equal(first.state.signals.warned, true);
  assert.equal(first.state.signals.degraded, true);
  assert.equal(first.state.selectedModel, 'cheap');
  assert.equal(first.state.scope, 'narrow');
  assert.deepEqual(first.state.ledger, {
    spentCredits: 95, reservedCheckpointCredits: 5, committedCredits: 100, estimatedCredits: 95,
    tokens: 950, modelCalls: 4, toolCalls: 4, sourceCount: 4, retries: 0, elapsedMs: 400
  });
  const resumed = await createContext({ artifactsPath }).workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'resume' });
  assert.equal(resumed.status, 'BUDGET_EXHAUSTED');
  assert.equal(resumed.execution.length, 0);
  const providerEvents = (await readFile(join(artifactsPath, 'provider-events.jsonl'), 'utf8')).trim().split('\n').map(JSON.parse);
  assert.equal(providerEvents.length, 4);
  const decisions = (await readFile(join(artifactsPath, 'decision-log.jsonl'), 'utf8')).trim().split('\n').map(JSON.parse);
  assert.ok(decisions.some((event) => event.event === 'terminal-run-blocked' && event.code === 'BUDGET_EXHAUSTED'));
  const partial = await readFile(join(artifactsPath, 'partial-result.md'), 'utf8');
  assert.match(partial, /Skipped items: source-5/);
  assert.match(partial, /Human decision required/);
});

test('two consecutive no-progress results stop the workflow below the financial cap', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  const result = await createContext({ artifactsPath }).workflow.run({ policy: fixture.policy, workItems: fixture.noProgressWorkItems, runId: 'no-progress' });
  assert.equal(result.status, 'NO_PROGRESS_LIMIT');
  assert.equal(result.state.ledger.spentCredits, 30);
  assert.equal(result.state.ledger.committedCredits, 35);
  assert.equal(result.state.ledger.modelCalls, 3);
  assert.equal(result.state.noProgressStreak, 2);
  assert.deepEqual(result.state.completedItems, ['verified-source']);
});

test('a resumed context keeps the original ledger and remaining allowance', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  const first = await createContext({ artifactsPath }).workflow.run({
    policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'first', maxCalls: 1
  });
  assert.equal(first.status, 'paused');
  assert.equal(first.state.ledger.spentCredits, 30);
  const resumed = await createContext({ artifactsPath }).workflow.run({
    policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'resumed', maxCalls: 1
  });
  assert.equal(resumed.status, 'paused');
  assert.equal(resumed.state.ledger.spentCredits, 65);
  assert.equal(resumed.state.ledger.committedCredits, 70);
  assert.equal(resumed.state.ledger.modelCalls, 2);
});

test('the persisted workload cannot be replaced to reset the ledger', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  await createContext({ artifactsPath }).workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'first', maxCalls: 1 });
  await assert.rejects(
    createContext({ artifactsPath }).workflow.run({ policy: fixture.policy, workItems: fixture.noProgressWorkItems, runId: 'changed-input' }),
    /Workflow input changed/
  );
});
