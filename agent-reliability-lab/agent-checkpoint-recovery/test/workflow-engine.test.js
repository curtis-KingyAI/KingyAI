import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createContext } from '../src/context.js';
import { SimulatedCrashError } from '../src/errors.js';
import { STAGES } from '../src/stages.js';

const fixture = JSON.parse(await readFile(new URL('../fixtures/workflow-input.json', import.meta.url)));

async function sandbox() {
  return mkdtemp(join(tmpdir(), 'kingy-checkpoints-'));
}

test('a crash after every committed stage resumes without rerunning unchanged upstream work', async (t) => {
  for (const crashAfter of STAGES) {
    const artifactsPath = await sandbox();
    t.after(() => rm(artifactsPath, { recursive: true, force: true }));
    const initial = createContext({ artifactsPath });
    await assert.rejects(
      initial.engine.run({ workflowInput: fixture, runId: `initial-${crashAfter}`, crashAfter }),
      (error) => error instanceof SimulatedCrashError && error.stage === crashAfter
    );

    const resumed = createContext({ artifactsPath });
    const result = await resumed.engine.run({ workflowInput: fixture, runId: `resume-${crashAfter}` });
    const index = STAGES.indexOf(crashAfter);
    assert.deepEqual(result.execution.filter((item) => item.status === 'reused').map((item) => item.stage), STAGES.slice(0, index + 1));
    assert.deepEqual(result.execution.filter((item) => item.status === 'completed').map((item) => item.stage), STAGES.slice(index + 1));
  }
});

test('a changed source document reuses collection but recomputes downstream checkpoints', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  await createContext({ artifactsPath }).engine.run({ workflowInput: fixture, runId: 'initial' });
  const changed = structuredClone(fixture);
  changed.sourceDocuments[0].claim = 'Aurora launch workflow was updated after the first source capture.';
  const result = await createContext({ artifactsPath }).engine.run({ workflowInput: changed, runId: 'source-change' });
  assert.deepEqual(result.execution, [
    { stage: 'collect', status: 'reused' },
    { stage: 'extract', status: 'completed' },
    { stage: 'outline', status: 'completed' },
    { stage: 'draft', status: 'completed' },
    { stage: 'fact-check', status: 'completed' },
    { stage: 'ready-for-review', status: 'completed' }
  ]);
});

test('a corrupted checkpoint artifact is not reused and invalidates only its downstream stages', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  await createContext({ artifactsPath }).engine.run({ workflowInput: fixture, runId: 'initial' });
  const corruptedPath = join(artifactsPath, 'stages', '04-draft.json');
  await writeFile(corruptedPath, '{"stage":"draft","output":"tampered"}\n');
  const result = await createContext({ artifactsPath }).engine.run({ workflowInput: fixture, runId: 'corruption-recovery' });
  assert.deepEqual(result.execution, [
    { stage: 'collect', status: 'reused' },
    { stage: 'extract', status: 'reused' },
    { stage: 'outline', status: 'reused' },
    { stage: 'draft', status: 'completed' },
    { stage: 'fact-check', status: 'completed' },
    { stage: 'ready-for-review', status: 'completed' }
  ]);
});

test('ready-for-review is impossible before fact-check checkpoint completion', async (t) => {
  const artifactsPath = await sandbox();
  t.after(() => rm(artifactsPath, { recursive: true, force: true }));
  const initial = createContext({ artifactsPath });
  await assert.rejects(initial.engine.run({ workflowInput: fixture, runId: 'initial-fact-check', crashAfter: 'fact-check' }), SimulatedCrashError);
  const state = await initial.store.read();
  assert.equal(state.stages['ready-for-review'], undefined);
  assert.equal(state.stages['fact-check'].status, 'complete');
});
