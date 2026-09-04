import { appendJsonLine } from './json-files.js';
import { AmbiguousTimeoutError, IdempotencyConflictError } from './errors.js';

export class IdempotentActionService {
  constructor({ store, publisher, outboxPath, operationLogPath, clock }) {
    this.store = store;
    this.publisher = publisher;
    this.outboxPath = outboxPath;
    this.operationLogPath = operationLogPath;
    this.clock = clock;
  }

  async execute(request, { simulateTimeoutAfterPublish = false } = {}) {
    const fingerprint = request.contentFingerprint;
    const command = {
      idempotencyKey: request.idempotencyKey,
      recordId: request.recordId,
      contentFingerprint: fingerprint
    };
    const reservation = await this.store.reserve({
      idempotencyKey: request.idempotencyKey,
      fingerprint,
      command
    });

    if (reservation.kind === 'conflict') {
      await this.#record('key-conflict', request);
      throw new IdempotencyConflictError(request.idempotencyKey);
    }
    if (reservation.kind === 'completed') {
      await this.#record('replayed-completed-result', request);
      return { disposition: 'replayed', publication: reservation.entry.result };
    }
    if (reservation.kind === 'created') {
      await appendJsonLine(this.outboxPath, {
        event: 'outbox-reserved', timestamp: this.clock.now(), idempotencyKey: request.idempotencyKey,
        recordId: request.recordId, contentFingerprint: fingerprint
      });
    }

    const alreadyPublished = await this.publisher.findByKey(request.idempotencyKey);
    if (alreadyPublished) {
      await this.store.complete(request.idempotencyKey, alreadyPublished, 'publisher-reconciliation');
      await this.#record('reconciled-pending-result', request);
      return { disposition: 'reconciled', publication: alreadyPublished };
    }

    const published = await this.publisher.publish(command);
    await this.#record('publisher-accepted', request);
    if (simulateTimeoutAfterPublish) {
      await this.#record('ambiguous-timeout', request);
      throw new AmbiguousTimeoutError();
    }

    await this.store.complete(request.idempotencyKey, published.publication, 'initial-request');
    await this.#record('completed-new-result', request);
    return { disposition: 'created', publication: published.publication };
  }

  async #record(event, request) {
    await appendJsonLine(this.operationLogPath, {
      event,
      timestamp: this.clock.now(),
      correlationId: request.correlationId,
      idempotencyKey: request.idempotencyKey,
      recordId: request.recordId,
      contentFingerprint: request.contentFingerprint
    });
  }
}
