import { appendJsonLine } from './json-files.js';

export class ProvenanceLog {
  constructor(path, clock) {
    this.path = path;
    this.clock = clock;
  }

  async record({ correlationId, serverId, toolName, code, inputFingerprint, outputFingerprint }) {
    const event = {
      timestamp: this.clock.now(),
      correlationId,
      serverId,
      toolName,
      code,
      inputFingerprint,
      outputFingerprint
    };
    await appendJsonLine(this.path, event);
    return event;
  }
}
