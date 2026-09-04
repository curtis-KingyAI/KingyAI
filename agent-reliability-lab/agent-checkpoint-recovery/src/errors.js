export class SimulatedCrashError extends Error {
  constructor(stage) {
    super(`Crash injected after the ${stage} checkpoint was committed.`);
    this.name = 'SimulatedCrashError';
    this.code = 'SIMULATED_CRASH';
    this.stage = stage;
  }
}
