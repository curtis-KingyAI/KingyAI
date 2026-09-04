import { access, readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const labs = JSON.parse(await readFile(resolve(root, 'labs.json'))).labs;
const requiredHubFiles = ['README.md', 'QUICKSTART.md', 'CONTROL-MATRIX.md', 'EVIDENCE-METHODOLOGY.md', 'EDITORIAL-LAUNCH.md'];

for (const file of requiredHubFiles) await access(resolve(root, file));
for (const lab of labs) {
  const labRoot = resolve(root, lab.path);
  for (const file of ['README.md', 'ARCHITECTURE.md', 'THREAT-MODEL.md', 'RUNBOOK.md', 'package.json']) {
    await access(resolve(labRoot, file));
  }
}
if (labs.length !== 6) throw new Error(`Expected exactly six labs; found ${labs.length}.`);
console.log(`Hub validation passed: ${labs.length} runnable labs and ${requiredHubFiles.length} hub documents found.`);
