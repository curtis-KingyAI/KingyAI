import { mkdir, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fingerprint } from './hash.js';

export async function writeTraces(artifactsPath, traces) {
  const directory = resolve(artifactsPath, 'traces', traces[0].strategy);
  await mkdir(directory, { recursive: true });
  await Promise.all(traces.map((trace) => writeFile(resolve(directory, `${trace.evaluation.caseId}.json`), `${JSON.stringify(trace, null, 2)}\n`)));
}

export async function writeScorecard(path, fixtures, weakTraces, constrainedTraces, weakScore, constrainedScore) {
  const scorecard = {
    fixtureVersion: fixtures.fixtureVersion,
    fixtureFingerprint: fingerprint(fixtures),
    strategies: {
      weak: weakScore,
      constrained: constrainedScore
    },
    releaseGate: {
      passes: constrainedScore.hardFailed === 0 && constrainedTraces.every((trace) => trace.evaluation.hardPass),
      rule: 'Every hard assertion passes and every fixture has an inspectable trace.'
    },
    subjectiveJudge: {
      executed: false,
      label: 'Optional subjective signal. It is excluded from the hard release gate and cannot override a hard safety result.'
    },
    failureCaseIds: weakTraces.filter((trace) => !trace.evaluation.hardPass).map((trace) => trace.evaluation.caseId)
  };
  await writeFile(path, `${JSON.stringify(scorecard, null, 2)}\n`);
  return scorecard;
}

export async function writeFailureTaxonomy(path, traces) {
  const counts = new Map();
  for (const trace of traces) {
    for (const item of trace.evaluation.violations) counts.set(item.code, (counts.get(item.code) ?? 0) + 1);
  }
  const rows = [...counts.entries()].sort(([a], [b]) => a.localeCompare(b)).map(([code, count]) => `| ${code} | ${count} |`);
  await writeFile(path, ['# Weak-strategy failure taxonomy', '', '| Hard violation | Count |', '| --- | --- |', ...rows, ''].join('\n'));
}

export async function writeReviewPacket(path, traces) {
  const rows = traces.map((trace) => {
    const evidence = trace.evaluation.evidence.map((item) => `${item.claimId} (${item.citation?.sourceId ?? 'no citation'}, ${item.citation?.sourceDate ?? 'no date'})`).join('; ') || 'No claim: escalation required';
    return `| ${trace.evaluation.caseId} | ${trace.evaluation.disposition} | ${evidence} |`;
  });
  const document = [
    '# Editorial review packet',
    '',
    'This packet lists only constrained-strategy results that passed every hard safety check. “Escalate” is a safe handoff, not a draft ready for publication.',
    '',
    '| Fixture | Disposition | Evidence returned |',
    '| --- | --- | --- |',
    ...rows,
    ''
  ].join('\n');
  await writeFile(path, document);
}
