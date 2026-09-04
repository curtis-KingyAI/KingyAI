import { FixedClock } from './clock.js';
import { IdempotencyStore } from './idempotency-store.js';
import { MockPublisher } from './mock-publisher.js';
import { IdempotentActionService } from './action-service.js';

export function createContext({ artifactsPath, now = '2026-09-03T12:00:00.000Z' }) {
  const clock = new FixedClock(now);
  const store = new IdempotencyStore(`${artifactsPath}/idempotency-store.json`, clock);
  const publisher = new MockPublisher({
    statePath: `${artifactsPath}/publisher-state.json`,
    eventPath: `${artifactsPath}/publisher-events.jsonl`,
    clock
  });
  const service = new IdempotentActionService({
    store,
    publisher,
    outboxPath: `${artifactsPath}/outbox.jsonl`,
    operationLogPath: `${artifactsPath}/operation-events.jsonl`,
    clock
  });
  return { clock, store, publisher, service };
}
