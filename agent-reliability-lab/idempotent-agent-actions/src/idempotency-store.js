import { readJson, writeJsonAtomically } from './json-files.js';

export class IdempotencyStore {
  constructor(path, clock) {
    this.path = path;
    this.clock = clock;
  }

  async reserve({ idempotencyKey, fingerprint, command }) {
    const state = await readJson(this.path, { entries: {} });
    const existing = state.entries[idempotencyKey];
    if (existing) {
      if (existing.fingerprint !== fingerprint) return { kind: 'conflict', entry: existing };
      return { kind: existing.status === 'completed' ? 'completed' : 'pending', entry: existing };
    }

    const entry = {
      idempotencyKey,
      fingerprint,
      status: 'pending',
      createdAt: this.clock.now(),
      command,
      outbox: { state: 'ready', createdAt: this.clock.now() },
      result: null
    };
    state.entries[idempotencyKey] = entry;
    await writeJsonAtomically(this.path, state);
    return { kind: 'created', entry };
  }

  async complete(idempotencyKey, result, resolvedBy) {
    const state = await readJson(this.path, { entries: {} });
    const entry = state.entries[idempotencyKey];
    if (!entry) throw new Error(`No idempotency entry exists for ${idempotencyKey}`);
    entry.status = 'completed';
    entry.completedAt = this.clock.now();
    entry.result = result;
    entry.resolvedBy = resolvedBy;
    entry.outbox = { ...entry.outbox, state: 'delivered', deliveredAt: this.clock.now() };
    await writeJsonAtomically(this.path, state);
    return entry;
  }

  async snapshot() {
    return readJson(this.path, { entries: {} });
  }
}
