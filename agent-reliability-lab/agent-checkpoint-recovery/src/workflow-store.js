import { readJson, writeJsonAtomically } from './json-files.js';

export class WorkflowStore {
  constructor(path) {
    this.path = path;
  }

  async read() {
    return readJson(this.path, { schemaVersion: '0.1.0', workflowVersion: '0.1.0', stages: {} });
  }

  async write(state) {
    await writeJsonAtomically(this.path, state);
  }

  async invalidateFrom(state, stageNames, index, reason) {
    const invalidated = [];
    for (const stage of stageNames.slice(index)) {
      if (state.stages[stage]) {
        delete state.stages[stage];
        invalidated.push(stage);
      }
    }
    if (invalidated.length > 0) {
      state.lastInvalidation = { reason, stages: invalidated };
      await this.write(state);
    }
    return invalidated;
  }
}
