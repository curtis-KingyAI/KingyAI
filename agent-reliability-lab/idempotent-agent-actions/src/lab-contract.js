export function validateRunSummary(summary) {
  const required = ['lab', 'labVersion', 'fixtureId', 'startedAt', 'outcome', 'assertions', 'evidence', 'limitations'];
  const missing = required.filter((key) => !(key in summary));
  if (missing.length > 0) throw new Error(`Run summary is missing: ${missing.join(', ')}`);
  return summary;
}
