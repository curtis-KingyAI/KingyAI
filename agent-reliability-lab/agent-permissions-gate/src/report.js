import { writeFile } from 'node:fs/promises';

export async function writeDecisionReport(path, steps) {
  const rows = steps.map((step) => `| ${step.name} | ${step.decision} | ${step.reasonCode} |`);
  const report = [
    '# Permission-gate decision report',
    '',
    '| Scenario | Decision | Reason |',
    '| --- | --- | --- |',
    ...rows,
    '',
    'The prompt text supplied to the gateway is deliberately not used to authorize actions and is not written to the audit log.',
    ''
  ].join('\n');
  await writeFile(path, report);
}
