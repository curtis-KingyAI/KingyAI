import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext } from '../src/context.js';
import { writeCostReport } from '../src/report.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixturePath = resolve(root, 'fixtures', 'scenarios.json');
const fixture = JSON.parse(await readFile(fixturePath));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await cp(fixturePath, resolve(artifacts, 'scenarios.json'));

const hardPath = resolve(artifacts, 'hard-budget');
let hardContext = createContext({ artifactsPath: hardPath });
const hardBudget = await hardContext.workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'hard-budget' });
hardContext = createContext({ artifactsPath: hardPath });
const hardResume = await hardContext.workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'hard-budget-resume' });

const noProgressPath = resolve(artifacts, 'no-progress');
const noProgress = await createContext({ artifactsPath: noProgressPath }).workflow.run({ policy: fixture.policy, workItems: fixture.noProgressWorkItems, runId: 'no-progress' });

const continuityPath = resolve(artifacts, 'resume-continuity');
await createContext({ artifactsPath: continuityPath }).workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'continuity-first', maxCalls: 1 });
const continuity = await createContext({ artifactsPath: continuityPath }).workflow.run({ policy: fixture.policy, workItems: fixture.hardBudgetWorkItems, runId: 'continuity-resume', maxCalls: 1 });

await writeCostReport(resolve(artifacts, 'cost-circuit-breaker-report.md'), hardBudget, noProgress, continuity);
const assertions = [
  ['warning-recorded', hardBudget.state.signals.warned],
  ['graceful-degradation-recorded', hardBudget.state.signals.degraded && hardBudget.state.selectedModel === 'cheap' && hardBudget.state.scope === 'narrow'],
  ['hard-budget-stop-and-checkpoint', hardBudget.status === 'BUDGET_EXHAUSTED' && hardBudget.state.ledger.committedCredits === 100],
  ['no-provider-call-after-hard-cap', hardResume.status === 'BUDGET_EXHAUSTED' && hardResume.execution.length === 0 && hardResume.state.ledger.modelCalls === 4],
  ['no-progress-stops-before-financial-cap', noProgress.status === 'NO_PROGRESS_LIMIT' && noProgress.state.ledger.spentCredits === 30 && noProgress.state.ledger.modelCalls === 3],
  ['resume-budget-continuity', continuity.status === 'paused' && continuity.state.ledger.spentCredits === 65 && continuity.state.ledger.committedCredits === 70]
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'agent-cost-circuit-breaker',
  labVersion: '0.1.0',
  fixtureId: fixture.fixtureId,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/budget-policy.json', 'artifacts/hard-budget/cost-ledger.jsonl', 'artifacts/hard-budget/decision-log.jsonl', 'artifacts/hard-budget/partial-result.md', 'artifacts/cost-circuit-breaker-report.md'],
  limitations: ['Fake credits and local accounting are not provider billing enforcement.', 'Checkpoint reserve is accounting-only in this mock; no paid checkpoint call occurs.']
});
await writeFile(resolve(artifacts, 'budget-policy.json'), `${JSON.stringify(fixture.policy, null, 2)}\n`);
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

console.table([
  { scenario: 'hard budget', status: hardBudget.status, modelCalls: hardBudget.state.ledger.modelCalls, spend: hardBudget.state.ledger.spentCredits, committed: hardBudget.state.ledger.committedCredits },
  { scenario: 'no progress', status: noProgress.status, modelCalls: noProgress.state.ledger.modelCalls, spend: noProgress.state.ledger.spentCredits, committed: noProgress.state.ledger.committedCredits },
  { scenario: 'resume continuity', status: continuity.status, modelCalls: continuity.state.ledger.modelCalls, spend: continuity.state.ledger.spentCredits, committed: continuity.state.ledger.committedCredits }
]);
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'run-summary.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
