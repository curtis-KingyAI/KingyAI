import { mkdir, rm, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const artifacts = resolve(root, 'artifacts');
await rm(artifacts, { recursive: true, force: true });
await mkdir(artifacts, { recursive: true });
await writeFile(resolve(artifacts, '.gitkeep'), '');
console.log('Removed generated artifacts for this repository only.');
