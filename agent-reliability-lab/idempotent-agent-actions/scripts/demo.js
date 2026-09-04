import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createContext } from '../src/context.js';
import { AmbiguousTimeoutError, IdempotencyConflictError } from '../src/errors.js';
import { writeDeduplicationReport } from '../src/report.js';
import { validateRunSummary } from '../src/lab-contract.js';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
const fixturePath = resolve(root, 'fixtures', 'publication-request.json');
const fixture = JSON.parse(await readFile(fixturePath));

await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await cp(fixturePath, resolve(artifacts, 'publication-request.json'));

const attempts = [];
let context = createContext({ artifactsPath: artifacts });
const makeRequest = (number, overrides = {}) => ({
  ...fixture,
  correlationId: `demo-attempt-${String(number).padStart(3, '0')}`,
  ...overrides
});

try {
  await context.service.execute(makeRequest(1), { simulateTimeoutAfterPublish: true });
} catch (error) {
  if (!(error instanceof AmbiguousTimeoutError)) throw error;
  attempts.push({ number: 1, idempotencyKey: fixture.idempotencyKey, outcome: error.code });
}

for (let number = 2; number <= 10; number += 1) {
  if (number === 2) context = createContext({ artifactsPath: artifacts });
  const result = await context.service.execute(makeRequest(number));
  attempts.push({ number, idempotencyKey: fixture.idempotencyKey, outcome: result.disposition, publicationId: result.publication.publicationId });
}

let conflictCode;
try {
  await context.service.execute(makeRequest(11, { contentFingerprint: 'sha256:aurora-conflicting-content' }));
} catch (error) {
  if (!(error instanceof IdempotencyConflictError)) throw error;
  conflictCode = error.code;
}

const revised = await context.service.execute(makeRequest(12, {
  idempotencyKey: 'publish:launch-aurora:v2',
  contentFingerprint: 'sha256:aurora-v2-fixture'
}));
const publisherState = await context.publisher.snapshot();
const retryPublications = publisherState.publications.filter((item) => item.idempotencyKey === fixture.idempotencyKey);

await writeDeduplicationReport(resolve(artifacts, 'deduplication-report.md'), attempts, publisherState);
const assertions = [
  ['ten-attempts.one-publication', retryPublications.length === 1 && publisherState.publishCallCount === 2],
  ['ambiguous-timeout.reconciled', attempts[0].outcome === 'AMBIGUOUS_TIMEOUT' && attempts[1].outcome === 'reconciled'],
  ['completed-retries.return-stored-result', attempts.slice(2).every((attempt) => attempt.outcome === 'replayed')],
  ['same-key-different-payload.conflict', conflictCode === 'IDEMPOTENCY_KEY_CONFLICT'],
  ['new-versioned-key.intentional-publication', revised.disposition === 'created' && publisherState.publications.length === 2]
].map(([id, passed]) => ({ id, result: passed ? 'pass' : 'fail' }));

const summary = validateRunSummary({
  lab: 'idempotent-agent-actions',
  labVersion: '0.1.0',
  fixtureId: fixture.fixtureId,
  startedAt: '2026-09-03T12:00:00.000Z',
  outcome: assertions.every((assertion) => assertion.result === 'pass') ? 'protected' : 'failed',
  assertions,
  evidence: ['artifacts/idempotency-store.json', 'artifacts/outbox.jsonl', 'artifacts/publisher-events.jsonl', 'artifacts/deduplication-report.md'],
  limitations: ['Local JSON files are not a transactional database or distributed lock.', 'Mock publisher only; no live CMS or provider idempotency was tested.']
});
await writeFile(resolve(artifacts, 'run-summary.json'), `${JSON.stringify(summary, null, 2)}\n`);

console.table(attempts);
console.log(`Retry-key publications: ${retryPublications.length}; total intended publications: ${publisherState.publications.length}`);
console.log(`Outcome: ${summary.outcome}. Inspect ${resolve(artifacts, 'run-summary.json')}`);
if (summary.outcome !== 'protected') process.exitCode = 1;
