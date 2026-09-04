export function constrainedCandidate(testCase) {
  const expected = testCase.expectations;
  return {
    status: expected.requireEscalation ? 'escalate' : 'draft',
    claims: expected.requireEscalation ? [] : expected.allowedClaims.map((claim) => ({
      id: claim.id,
      citation: { sourceId: claim.sourceId, sourceDate: claim.sourceDate },
      boundary: claim.boundary
    })),
    limitations: [...expected.requiredLimitations],
    uncertaintyMarked: expected.requireUncertainty,
    disclosure: expected.requireDisclosure ?? null,
    escalation: expected.requireEscalation ? { required: true, reason: expected.escalationReason } : { required: false },
    sections: ['Summary', 'Evidence']
  };
}

export function weakCandidate(testCase) {
  return {
    status: 'draft',
    claims: testCase.weak.claims ?? [],
    limitations: testCase.weak.limitations ?? [],
    uncertaintyMarked: testCase.weak.uncertaintyMarked ?? false,
    disclosure: testCase.weak.disclosure ?? null,
    escalation: testCase.weak.escalation ?? { required: false },
    sections: testCase.weak.sections ?? ['Summary', 'Evidence']
  };
}
