import { appendJsonLine, readJson, writeJsonAtomically } from './json-files.js';

export class MockPublisher {
  constructor({ statePath, eventPath, clock }) {
    this.statePath = statePath;
    this.eventPath = eventPath;
    this.clock = clock;
  }

  async findByKey(idempotencyKey) {
    const state = await readJson(this.statePath, { publishCallCount: 0, publications: [] });
    return state.publications.find((publication) => publication.idempotencyKey === idempotencyKey) ?? null;
  }

  async publish(command) {
    const state = await readJson(this.statePath, { publishCallCount: 0, publications: [] });
    const existing = state.publications.find((publication) => publication.idempotencyKey === command.idempotencyKey);
    if (existing) return { publication: existing, created: false };

    const publication = {
      publicationId: `mock-post-${String(state.publications.length + 1).padStart(3, '0')}`,
      idempotencyKey: command.idempotencyKey,
      recordId: command.recordId,
      contentFingerprint: command.contentFingerprint,
      publishedAt: this.clock.now()
    };
    state.publishCallCount += 1;
    state.publications.push(publication);
    await writeJsonAtomically(this.statePath, state);
    await appendJsonLine(this.eventPath, {
      event: 'publication-created',
      timestamp: this.clock.now(),
      publicationId: publication.publicationId,
      idempotencyKey: publication.idempotencyKey,
      recordId: publication.recordId
    });
    return { publication, created: true };
  }

  async snapshot() {
    return readJson(this.statePath, { publishCallCount: 0, publications: [] });
  }
}
