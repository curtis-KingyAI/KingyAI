import { readJson, writeJsonAtomically } from './json-files.js';

export class WorkflowStore {
  constructor(path) {
    this.path = path;
  }

  async read() {
    return readJson(this.path, null);
  }

  async write(state) {
    await writeJsonAtomically(this.path, state);
  }
}
