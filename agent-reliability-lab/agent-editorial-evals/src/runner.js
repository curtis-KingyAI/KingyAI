import { evaluateCandidate } from './evaluator.js';
import { constrainedCandidate, weakCandidate } from './strategies.js';

export function runStrategy(fixtures, strategy) {
  const candidateFor = strategy === 'constrained' ? constrainedCandidate : weakCandidate;
  return fixtures.cases.map((testCase) => {
    const candidate = candidateFor(testCase);
    const evaluation = evaluateCandidate(testCase, candidate);
    return { fixtureVersion: fixtures.fixtureVersion, strategy, candidate, evaluation };
  });
}

export function score(traces) {
  const hardPassed = traces.filter((trace) => trace.evaluation.hardPass).length;
  return {
    totalCases: traces.length,
    hardPassed,
    hardFailed: traces.length - hardPassed,
    readyForReview: traces.filter((trace) => trace.evaluation.disposition === 'ready-for-review').length,
    escalated: traces.filter((trace) => trace.evaluation.disposition === 'escalate').length,
    blocked: traces.filter((trace) => trace.evaluation.disposition === 'blocked').length
  };
}
