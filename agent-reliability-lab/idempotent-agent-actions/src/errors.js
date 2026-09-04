export class AmbiguousTimeoutError extends Error {
  constructor() {
    super('The publisher accepted the action, but the caller timed out before completion was recorded.');
    this.name = 'AmbiguousTimeoutError';
    this.code = 'AMBIGUOUS_TIMEOUT';
  }
}

export class IdempotencyConflictError extends Error {
  constructor(key) {
    super(`The idempotency key ${key} was reused with a different payload fingerprint.`);
    this.name = 'IdempotencyConflictError';
    this.code = 'IDEMPOTENCY_KEY_CONFLICT';
  }
}
