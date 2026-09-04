import { readFile } from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
const labs = JSON.parse(await readFile(resolve(root, 'labs.json'))).labs;

function run(command, args, cwd) {
  return new Promise((resolveRun, rejectRun) => {
    const child = spawn(command, args, { cwd, stdio: 'inherit' });
    child.on('error', rejectRun);
    child.on('exit', (code) => code === 0 ? resolveRun() : rejectRun(new Error(`${command} ${args.join(' ')} failed in ${cwd} with ${code}`)));
  });
}

for (const lab of labs) {
  const cwd = resolve(root, lab.path);
  console.log(`\n== ${lab.id} ==`);
  await run('npm', ['test'], cwd);
  await run('npm', ['run', 'demo'], cwd);
}
console.log(`\nVerified all ${labs.length} labs.`);
