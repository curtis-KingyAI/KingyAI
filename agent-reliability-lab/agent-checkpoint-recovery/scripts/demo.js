import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext } from '../src/context.js';
import { SimulatedCrashError } from '../src/errors.js';
import { STAGES } from '../src/stages.js';
import { writeRecoveryReport } from '../src/report.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixturePath = resolve(root, 'fixtures', 'workflow-input.json');
const workflowInput = JSON.parse(await readFile(fixturePath));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await cp(fixturePath, resolve(artifacts, 'workflow-input.json'));

const scenarios = [];
for (const crashAfter of STAGES) {
  const scenarioPath = resolve(artifacts, `crash-after-${crashAfter}`);
  let context = createContext({ artifactsPath: scenarioPath });
  try {
    await context.engine.run({ workflowInput, runId: `initial-${crashAfter}`, crashAfter });
  } catch (error) {
    if (!(error instanceof SimulatedCrashError)) throw error;
  }
  context = createContext({ artifactsPath: scenarioPath });
  const resume = await context.engine.run({ workflowInput, runId: `resume-${crashAfter}` });
  scenarios.push({ crashAfter, resume });
}

const inputChangePath = resolve(artifacts, 'input-change');
let inputChangeContext = createContext({ artifactsPath: inputChangePath });
await inputChangeContext.engine.run({ workflowInput, runId: 'input-change-initial' });
const changedInput = structuredClone(workflowInput);
changedInput.sourceDocuments[1].claim = 'Aurora availability changed after the initial source check.';
inputChangeContext = createContext({ artifactsPath: inputChangePath });
const inputChange = await inputChangeContext.engine.run({ workflowInput: changedInput, runId: 'input-change-resume' });

await writeRecoveryReport(resolve(artifacts, 'crash-recovery-report.md'), scenarios, inputChange);
const assertions = [
  ...scenarios.map((scenario) => {
    const index = STAGES.indexOf(scenario.crashAfter);
    const reused = scenario.resume.execution.filter((item) => item.status === 'reused').map((item) => item.stage);
    const completed = scenario.resume.execution.filter((item) => item.status === 'completed').map((item) => item.stage);
    return [`crash-after-${scenario.crashAfter}.resumes-correctly`, JSON.stringify(reused) === JSON.stringify(STAGES.slice(0, index + 1)) && JSON.stringify(completed) === JSON.stringify(STAGES.slice(index + 1))];
  }),
  ['source-change.invalidates-downstream-only', JSON.stringify(inputChange.execution) === JSON.stringify([
    { stage: 'collect', status: 'reused' },
    { stage: 'extract', status: 'completed' },
    { stage: 'outline', status: 'completed' },
    { stage: 'draft', status: 'completed' },
    { stage: 'fact-check', status: 'completed' },
    { stage: 'ready-for-review', status: 'completed' }
  ])]
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'agent-checkpoint-recovery',
  labVersion: '0.1.0',
  fixtureId: workflowInput.fixtureId,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/crash-recovery-report.md', 'artifacts/crash-after-collect/workflow-state.json', 'artifacts/crash-after-ready-for-review/transition-log.jsonl', 'artifacts/input-change/workflow-state.json'],
  limitations: ['JSON checkpoints are a local teaching implementation, not a distributed workflow engine.', 'No external side effect is performed or recovered.']
});
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

console.table(scenarios.map(({ crashAfter, resume }) => ({
  crashAfter,
  reused: resume.execution.filter((item) => item.status === 'reused').map((item) => item.stage).join(', ') || 'none',
  rerun: resume.execution.filter((item) => item.status === 'completed').map((item) => item.stage).join(', ') || 'none'
})));
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'run-summary.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
