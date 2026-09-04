import { writeFile } from 'node:fs/promises';

export async function writeDeduplicationReport(path, attempts, publisherState) {
  const rows = attempts.map((attempt) => `| ${attempt.number} | ${attempt.outcome} | ${attempt.publicationId ?? '—'} |`);
  const report = [
    '# Idempotent publication report',
    '',
    '| Attempt | Outcome | Publication |',
    '| --- | --- | --- |',
    ...rows,
    '',
    `The retry key produced ${publisherState.publications.filter((item) => item.idempotencyKey === attempts[0].idempotencyKey).length} mocked publication(s).`,
    'The first request is deliberately ambiguous after the publisher accepts it; the first retry reconciles durable publisher state rather than publishing again.',
    ''
  ].join('\n');
  await writeFile(path, report);
}
