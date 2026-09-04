import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { evaluateCandidate } from '../src/evaluator.js';
import { runStrategy, score } from '../src/runner.js';
import { constrainedCandidate } from '../src/strategies.js';

const fixtures = JSON.parse(await readFile(new URL('../fixtures/cases.json', import.meta.url)));

test('constrained strategy passes every hard case and routes required escalations safely', () => {
  const traces = runStrategy(fixtures, 'constrained');
  const result = score(traces);
  assert.equal(result.totalCases, 12);
  assert.equal(result.hardPassed, 12);
  assert.equal(result.hardFailed, 0);
  assert.equal(result.escalated, 5);
  assert.ok(traces.every((trace) => trace.evaluation.hardPass));
});

test('weak strategy is blocked for unsupported claims, false citations, stale pricing, limits, and escalation', () => {
  const traces = runStrategy(fixtures, 'weak');
  const byId = Object.fromEntries(traces.map((trace) => [trace.evaluation.caseId, trace.evaluation.violations.map((item) => item.code)]));
  assert.equal(score(traces).hardFailed, 12);
  assert.ok(byId['unsupported-claim'].includes('UNSUPPORTED_CLAIM'));
  assert.ok(byId['supported-claim'].includes('MISSING_SOURCE'));
  assert.ok(byId['stale-price'].includes('STALE_PRICE_MARKED_CURRENT'));
  assert.ok(byId['stale-price'].includes('MISSING_LIMITATION'));
  assert.ok(byId['source-to-claim-mismatch'].includes('FALSE_CITATION'));
  assert.ok(byId['malformed-source'].includes('MALFORMED_SOURCE_USED'));
  assert.ok(byId['required-escalation'].includes('MISSED_REQUIRED_ESCALATION'));
  assert.ok(byId['uncertain-availability'].includes('UNCERTAINTY_UNMARKED'));
});

test('a regression that removes a required limitation is blocked even when citations are correct', () => {
  const testCase = fixtures.cases.find((item) => item.id === 'stale-price');
  const candidate = constrainedCandidate(testCase);
  candidate.limitations = [];
  const result = evaluateCandidate(testCase, candidate);
  assert.equal(result.hardPass, false);
  assert.equal(result.disposition, 'blocked');
  assert.ok(result.violations.some((item) => item.code === 'MISSING_LIMITATION'));
});

test('fixture version and deterministic candidate fingerprints remain visible', () => {
  const first = runStrategy(fixtures, 'constrained');
  const second = runStrategy(fixtures, 'constrained');
  assert.equal(fixtures.fixtureVersion, '2026-09-03.1');
  assert.deepEqual(first.map((trace) => trace.evaluation.candidateFingerprint), second.map((trace) => trace.evaluation.candidateFingerprint));
  assert.ok(first.every((trace) => trace.fixtureVersion === fixtures.fixtureVersion));
});
