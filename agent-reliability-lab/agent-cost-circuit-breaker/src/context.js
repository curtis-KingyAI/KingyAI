import { FixedClock } from './clock.js';
import { WorkflowStore } from './workflow-store.js';
import { FakeMeteredModel } from './fake-metered-model.js';
import { CostGovernedWorkflow } from './cost-governor.js';

export function createContext({ artifactsPath, now = '2026-09-03T12:00:00.000Z' }) {
  const clock = new FixedClock(now);
  const store = new WorkflowStore(`${artifactsPath}/workflow-state.json`);
  const model = new FakeMeteredModel({ eventPath: `${artifactsPath}/provider-events.jsonl`, clock });
  const workflow = new CostGovernedWorkflow({ artifactsPath, store, model, clock });
  return { clock, store, model, workflow };
}
