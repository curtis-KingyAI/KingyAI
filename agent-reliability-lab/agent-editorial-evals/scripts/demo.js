import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runStrategy, score } from '../src/runner.js';
import { writeFailureTaxonomy, writeReviewPacket, writeScorecard, writeTraces } from '../src/reports.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixturePath = resolve(root, 'fixtures', 'cases.json');
const fixtures = JSON.parse(await readFile(fixturePath));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await cp(fixturePath, resolve(artifacts, 'source-snapshots.json'));
await writeFile(resolve(artifacts, 'expectations.json'), `${JSON.stringify({ fixtureVersion: fixtures.fixtureVersion, cases: fixtures.cases.map((item) => ({ id: item.id, expectations: item.expectations })) }, null, 2)}\n`);

const weakTraces = runStrategy(fixtures, 'weak');
const constrainedTraces = runStrategy(fixtures, 'constrained');
const weakScore = score(weakTraces);
const constrainedScore = score(constrainedTraces);
await writeTraces(artifacts, weakTraces);
await writeTraces(artifacts, constrainedTraces);
const scorecard = await writeScorecard(resolve(artifacts, 'scorecard.json'), fixtures, weakTraces, constrainedTraces, weakScore, constrainedScore);
await writeFailureTaxonomy(resolve(artifacts, 'failure-taxonomy.md'), weakTraces);
await writeReviewPacket(resolve(artifacts, 'editor-review-packet.md'), constrainedTraces);

const assertions = [
  ['weak-strategy.regressions-detected', weakScore.hardFailed === fixtures.cases.length],
  ['constrained-strategy.all-hard-cases-pass', constrainedScore.hardPassed === fixtures.cases.length && constrainedScore.hardFailed === 0],
  ['release-gate.passes-only-safe-traces', scorecard.releaseGate.passes],
  ['required-escalations.stopped-before-review', constrainedTraces.filter((trace) => trace.evaluation.disposition === 'escalate').length === 5],
  ['subjective-judge.cannot-override-hard-gate', scorecard.subjectiveJudge.executed === false]
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'agent-editorial-evals',
  labVersion: '0.1.0',
  fixtureId: fixtures.fixtureVersion,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/source-snapshots.json', 'artifacts/expectations.json', 'artifacts/traces/weak', 'artifacts/traces/constrained', 'artifacts/scorecard.json', 'artifacts/editor-review-packet.md'],
  limitations: ['Fixed local fixtures do not prove factual correctness on the open web.', 'No model judge runs in this offline lab; subjective quality cannot override hard safety.']
});
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

console.table([
  { strategy: 'weak', ...weakScore },
  { strategy: 'constrained', ...constrainedScore }
]);
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'scorecard.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
