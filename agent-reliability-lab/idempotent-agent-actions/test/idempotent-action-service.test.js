import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createContext } from '../src/context.js';
import { AmbiguousTimeoutError, IdempotencyConflictError } from '../src/errors.js';

async function sandbox() {
  const artifactsPath = await mkdtemp(join(tmpdir(), 'kingy-idempotency-'));
  return { artifactsPath, context: createContext({ artifactsPath }) };
}

function request(overrides = {}) {
  return {
    idempotencyKey: 'publish:launch-aurora:v1',
    recordId: 'launch-aurora',
    contentFingerprint: 'sha256:aurora-v1-fixture',
    correlationId: 'test-attempt-001',
    ...overrides
  };
}

test('ten attempts after an ambiguous timeout create exactly one publication', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  await assert.rejects(
    box.context.service.execute(request(), { simulateTimeoutAfterPublish: true }),
    (error) => error instanceof AmbiguousTimeoutError && error.code === 'AMBIGUOUS_TIMEOUT'
  );

  let context = createContext({ artifactsPath: box.artifactsPath });
  const results = [];
  for (let attempt = 2; attempt <= 10; attempt += 1) {
    if (attempt === 2) context = createContext({ artifactsPath: box.artifactsPath });
    results.push(await context.service.execute(request({ correlationId: `test-attempt-${attempt}` })));
  }
  const publisher = await context.publisher.snapshot();
  assert.equal(results[0].disposition, 'reconciled');
  assert.ok(results.slice(1).every((result) => result.disposition === 'replayed'));
  assert.equal(publisher.publishCallCount, 1);
  assert.equal(publisher.publications.length, 1);
  assert.equal(publisher.publications[0].publicationId, 'mock-post-001');
});

test('a retry with the same key and a different payload is rejected', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  await box.context.service.execute(request());
  await assert.rejects(
    box.context.service.execute(request({ contentFingerprint: 'sha256:changed-content' })),
    (error) => error instanceof IdempotencyConflictError && error.code === 'IDEMPOTENCY_KEY_CONFLICT'
  );
  const publisher = await box.context.publisher.snapshot();
  assert.equal(publisher.publications.length, 1);
});

test('a new versioned key intentionally produces a second publication', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  const first = await box.context.service.execute(request());
  const second = await box.context.service.execute(request({
    idempotencyKey: 'publish:launch-aurora:v2', contentFingerprint: 'sha256:aurora-v2-fixture'
  }));
  const publisher = await box.context.publisher.snapshot();
  assert.equal(first.disposition, 'created');
  assert.equal(second.disposition, 'created');
  assert.equal(publisher.publishCallCount, 2);
  assert.equal(publisher.publications.length, 2);
});

test('a pending outbox entry survives a simulated process restart and is reconciled', async (t) => {
  const box = await sandbox();
  t.after(() => rm(box.artifactsPath, { recursive: true, force: true }));
  await assert.rejects(box.context.service.execute(request(), { simulateTimeoutAfterPublish: true }), AmbiguousTimeoutError);
  const restarted = createContext({ artifactsPath: box.artifactsPath });
  const result = await restarted.service.execute(request({ correlationId: 'test-after-restart' }));
  const store = await restarted.store.snapshot();
  assert.equal(result.disposition, 'reconciled');
  assert.equal(store.entries[request().idempotencyKey].status, 'completed');
  assert.equal(store.entries[request().idempotencyKey].resolvedBy, 'publisher-reconciliation');
});
