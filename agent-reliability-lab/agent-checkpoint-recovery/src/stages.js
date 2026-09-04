import { fingerprint } from './hash.js';

export const STAGES = Object.freeze(['collect', 'extract', 'outline', 'draft', 'fact-check', 'ready-for-review']);

export function stageInput(stage, workflowInput, previousOutput) {
  switch (stage) {
    case 'collect':
      return { sourceManifest: workflowInput.sourceManifest };
    case 'extract':
      return { collected: previousOutput, sourceDocuments: workflowInput.sourceDocuments };
    case 'outline':
      return { extracted: previousOutput };
    case 'draft':
      return { outline: previousOutput, editorialBrief: workflowInput.editorialBrief };
    case 'fact-check':
      return { draft: previousOutput, sourceDocuments: workflowInput.sourceDocuments };
    case 'ready-for-review':
      return { factCheck: previousOutput };
    default:
      throw new Error(`Unknown stage: ${stage}`);
  }
}

export function executeStage(stage, input) {
  switch (stage) {
    case 'collect':
      return { sourceIds: input.sourceManifest.map((source) => source.id), sourceCount: input.sourceManifest.length };
    case 'extract':
      return {
        claims: input.sourceDocuments.map((source) => ({ sourceId: source.id, claim: source.claim, checkedAt: source.checkedAt }))
      };
    case 'outline':
      return { sections: input.extracted.claims.map((claim) => ({ heading: claim.claim, sourceId: claim.sourceId })) };
    case 'draft':
      return {
        title: input.editorialBrief.title,
        sections: input.outline.sections,
        limitation: input.editorialBrief.requiredLimitation
      };
    case 'fact-check':
      return {
        verified: input.draft.sections.every((section) => input.sourceDocuments.some((source) => source.id === section.sourceId)),
        sourceIds: input.draft.sections.map((section) => section.sourceId),
        limitationPresent: input.draft.limitation === 'Current availability requires source-date verification.'
      };
    case 'ready-for-review':
      return {
        status: input.factCheck.verified && input.factCheck.limitationPresent ? 'ready-for-review' : 'blocked',
        evidenceSourceIds: input.factCheck.sourceIds
      };
    default:
      throw new Error(`Unknown stage: ${stage}`);
  }
}

export function stageArtifact(stage, inputFingerprint, output) {
  return { stage, inputFingerprint, output, outputFingerprint: fingerprint(output) };
}
