import { writeFile } from 'node:fs/promises';

export async function writeSecurityReport(path, steps) {
  const rows = steps.map((step) => `| ${step.name} | ${step.code} | ${step.accepted ? 'yes' : 'no'} |`);
  const report = [
    '# MCP host security decision report',
    '',
    '| Scenario | Host result | Server output accepted? |',
    '| --- | --- | --- |',
    ...rows,
    '',
    'Prompt-injection text in a valid research response is retained only as untrusted evidence data. It cannot alter the host allowlist, invoke another tool, access a path, or publish a record.',
    'The provenance log stores identities, codes, and fingerprints—not raw prompts, response text, approval identifiers, or fixture nonces.',
    ''
  ].join('\n');
  await writeFile(path, report);
}
