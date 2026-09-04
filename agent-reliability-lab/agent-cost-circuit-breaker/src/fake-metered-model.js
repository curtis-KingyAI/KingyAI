import { appendJsonLine } from './json-files.js';

export class FakeMeteredModel {
  constructor({ eventPath, clock }) {
    this.eventPath = eventPath;
    this.clock = clock;
  }

  async invoke({ workItem, model, correlationId }) {
    const actualCostCredits = workItem[`${model}CostCredits`];
    if (actualCostCredits === undefined) throw new Error(`Fixture ${workItem.id} does not support ${model}.`);
    const result = {
      event: 'model-called',
      timestamp: this.clock.now(),
      correlationId,
      workItemId: workItem.id,
      model,
      actualCostCredits,
      tokens: workItem.tokens,
      toolCalls: workItem.toolCalls,
      sourceCount: workItem.sourceCount,
      retry: workItem.retry,
      progress: workItem.progress
    };
    await appendJsonLine(this.eventPath, result);
    return result;
  }
}
