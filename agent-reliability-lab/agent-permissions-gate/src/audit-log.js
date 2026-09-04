import { appendFile, mkdir } from 'node:fs/promises';
import { dirname } from 'node:path';

export class AuditLog {
  constructor(path) {
    this.path = path;
  }

  async record(event) {
    const safeEvent = {
      timestamp: event.timestamp,
      correlationId: event.correlationId,
      actor: event.actor,
      action: event.action,
      resource: event.resource,
      decision: event.decision,
      reasonCode: event.reasonCode
    };
    await mkdir(dirname(this.path), { recursive: true });
    await appendFile(this.path, `${JSON.stringify(safeEvent)}\n`);
    return safeEvent;
  }
}
