import { FixedClock } from './clock.js';
import { WorkflowEngine } from './workflow-engine.js';
import { WorkflowStore } from './workflow-store.js';

export function createContext({ artifactsPath, now = '2026-09-03T12:00:00.000Z' }) {
  const clock = new FixedClock(now);
  const store = new WorkflowStore(`${artifactsPath}/workflow-state.json`);
  const engine = new WorkflowEngine({ artifactsPath, store, clock });
  return { clock, store, engine };
}
