import { fingerprint } from './hash.js';

function violation(code, detail) {
  return { code, detail };
}

export function evaluateCandidate(testCase, candidate) {
  const expected = testCase.expectations;
  const sources = new Map(testCase.sources.map((source) => [source.id, source]));
  const allowed = new Map(expected.allowedClaims.map((claim) => [claim.id, claim]));
  const violations = [];

  for (const claim of candidate.claims ?? []) {
    const rule = allowed.get(claim.id);
    let citedSource = null;
    if (!claim.citation?.sourceId || !claim.citation?.sourceDate) {
      violations.push(violation('MISSING_CITATION', claim.id));
    } else {
      citedSource = sources.get(claim.citation.sourceId);
      if (!citedSource) violations.push(violation('MISSING_SOURCE', claim.id));
      else if (!citedSource.valid) violations.push(violation('MALFORMED_SOURCE_USED', claim.id));
    }
    if (!rule) {
      violations.push(violation('UNSUPPORTED_CLAIM', claim.id));
      continue;
    }
    if (citedSource?.valid && claim.citation.sourceId !== rule.sourceId) violations.push(violation('FALSE_CITATION', claim.id));
    else if (citedSource?.valid && claim.citation.sourceDate !== rule.sourceDate) violations.push(violation('CITATION_DATE_MISMATCH', claim.id));
    if (claim.boundary !== rule.boundary) {
      violations.push(violation(rule.boundary === 'historical' && claim.boundary === 'current' ? 'STALE_PRICE_MARKED_CURRENT' : 'CLAIM_BOUNDARY_MISMATCH', claim.id));
    }
  }

  for (const limitation of expected.requiredLimitations) {
    if (!(candidate.limitations ?? []).includes(limitation)) violations.push(violation('MISSING_LIMITATION', limitation));
  }
  if (expected.requireUncertainty && candidate.uncertaintyMarked !== true) violations.push(violation('UNCERTAINTY_UNMARKED', testCase.id));
  if (expected.requireDisclosure && candidate.disclosure !== expected.requireDisclosure) violations.push(violation('MISSING_DISCLOSURE', testCase.id));
  if (expected.requireEscalation && candidate.escalation?.required !== true) violations.push(violation('MISSED_REQUIRED_ESCALATION', testCase.id));
  if (expected.forbidDuplicateSections) {
    const sections = candidate.sections ?? [];
    if (new Set(sections).size !== sections.length) violations.push(violation('DUPLICATE_CONTENT', testCase.id));
  }

  const hardPass = violations.length === 0;
  const disposition = !hardPass ? 'blocked' : expected.requireEscalation ? 'escalate' : 'ready-for-review';
  return {
    caseId: testCase.id,
    category: testCase.category,
    candidateFingerprint: fingerprint(candidate),
    hardPass,
    disposition,
    violations,
    evidence: (candidate.claims ?? []).map((claim) => ({ claimId: claim.id, citation: claim.citation ?? null, boundary: claim.boundary ?? null }))
  };
}
