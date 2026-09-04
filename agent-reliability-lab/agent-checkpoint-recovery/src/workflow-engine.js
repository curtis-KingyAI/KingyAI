import { access } from 'node:fs/promises';
import { resolve } from 'node:path';
import { appendJsonLine, readJson, writeJsonAtomically } from './json-files.js';
import { fingerprint } from './hash.js';
import { SimulatedCrashError } from './errors.js';
import { executeStage, STAGES, stageArtifact, stageInput } from './stages.js';

export class WorkflowEngine {
  constructor({ artifactsPath, store, clock }) {
    this.artifactsPath = artifactsPath;
    this.store = store;
    this.clock = clock;
    this.stageDirectory = resolve(artifactsPath, 'stages');
    this.logPath = resolve(artifactsPath, 'transition-log.jsonl');
  }

  async run({ workflowInput, runId, crashAfter }) {
    const state = await this.store.read();
    const execution = [];
    let previousOutput = null;

    for (const [index, stage] of STAGES.entries()) {
      const input = stageInput(stage, workflowInput, previousOutput);
      const inputFingerprint = fingerprint(input);
      const checkpoint = state.stages[stage];
      const validation = await this.#validateCheckpoint(checkpoint, inputFingerprint);

      if (validation.valid) {
        previousOutput = validation.artifact.output;
        execution.push({ stage, status: 'reused' });
        await this.#log('checkpoint-reused', runId, stage, { inputFingerprint });
        continue;
      }

      const invalidated = await this.store.invalidateFrom(state, STAGES, index, validation.reason);
      if (invalidated.length > 0) {
        await this.#log('checkpoints-invalidated', runId, stage, { reason: validation.reason, stages: invalidated });
      }

      const output = executeStage(stage, input);
      const artifact = stageArtifact(stage, inputFingerprint, output);
      const artifactPath = resolve(this.stageDirectory, `${String(index + 1).padStart(2, '0')}-${stage}.json`);
      await writeJsonAtomically(artifactPath, artifact);
      state.stages[stage] = {
        status: 'complete',
        inputFingerprint,
        outputFingerprint: artifact.outputFingerprint,
        artifactPath: `artifacts/stages/${String(index + 1).padStart(2, '0')}-${stage}.json`,
        completedAt: this.clock.now(),
        workflowVersion: state.workflowVersion
      };
      await this.store.write(state);
      previousOutput = output;
      execution.push({ stage, status: 'completed' });
      await this.#log('checkpoint-complete', runId, stage, { inputFingerprint, outputFingerprint: artifact.outputFingerprint });

      if (crashAfter === stage) {
        await this.#log('crash-injected', runId, stage, {});
        throw new SimulatedCrashError(stage);
      }
    }

    return { execution, state: await this.store.read() };
  }

  async #validateCheckpoint(checkpoint, expectedInputFingerprint) {
    if (!checkpoint) return { valid: false, reason: 'checkpoint-missing' };
    if (checkpoint.status !== 'complete') return { valid: false, reason: 'checkpoint-incomplete' };
    if (checkpoint.inputFingerprint !== expectedInputFingerprint) return { valid: false, reason: 'input-fingerprint-changed' };
    const absoluteArtifactPath = resolve(this.artifactsPath, checkpoint.artifactPath.replace(/^artifacts\//, ''));
    try {
      await access(absoluteArtifactPath);
      const artifact = await readJson(absoluteArtifactPath);
      if (artifact.outputFingerprint !== checkpoint.outputFingerprint) return { valid: false, reason: 'output-fingerprint-mismatch' };
      if (fingerprint(artifact.output) !== checkpoint.outputFingerprint) return { valid: false, reason: 'artifact-content-corrupted' };
      return { valid: true, artifact };
    } catch (error) {
      if (error.code === 'ENOENT') return { valid: false, reason: 'artifact-missing' };
      throw error;
    }
  }

  async #log(event, runId, stage, detail) {
    await appendJsonLine(this.logPath, { event, runId, stage, timestamp: this.clock.now(), ...detail });
  }
}
