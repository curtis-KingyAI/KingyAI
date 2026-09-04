import { writeFile } from 'node:fs/promises';

function stagesByStatus(execution, status) {
  return execution.filter((item) => item.status === status).map((item) => item.stage).join(', ') || 'none';
}

export async function writeRecoveryReport(path, scenarios, inputChange) {
  const rows = scenarios.map((scenario) => `| ${scenario.crashAfter} | ${stagesByStatus(scenario.resume.execution, 'reused')} | ${stagesByStatus(scenario.resume.execution, 'completed')} |`);
  const report = [
    '# Checkpoint recovery report',
    '',
    '| Forced crash after | Reused on restart | Completed on restart |',
    '| --- | --- | --- |',
    ...rows,
    '',
    '## Input-change scenario',
    '',
    `When a source document changed, the runner reused: ${stagesByStatus(inputChange.execution, 'reused')}.`,
    `It recomputed: ${stagesByStatus(inputChange.execution, 'completed')}.`,
    '',
    'A checkpoint is reused only when its stored input fingerprint and output artifact both verify.',
    ''
  ].join('\n');
  await writeFile(path, report);
}
