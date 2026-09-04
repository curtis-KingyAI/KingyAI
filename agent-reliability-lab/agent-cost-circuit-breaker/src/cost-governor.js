import { resolve } from 'node:path';
import { appendJsonLine } from './json-files.js';
import { fingerprint } from './hash.js';
import { writePartialResult } from './partial-result.js';

function initialState(policy, workItems, clock) {
  const reserve = policy.checkpointReserveCredits;
  return {
    schemaVersion: '0.1.0',
    policy,
    workItemsFingerprint: fingerprint(workItems),
    status: 'active',
    nextWorkIndex: 0,
    selectedModel: 'primary',
    scope: 'full',
    noProgressStreak: 0,
    completedItems: [],
    terminal: null,
    signals: { warned: false, degraded: false },
    ledger: {
      spentCredits: 0,
      reservedCheckpointCredits: reserve,
      committedCredits: reserve,
      estimatedCredits: 0,
      tokens: 0,
      modelCalls: 0,
      toolCalls: 0,
      sourceCount: 0,
      retries: 0,
      elapsedMs: 0
    },
    createdAt: clock.now()
  };
}

export class CostGovernedWorkflow {
  constructor({ artifactsPath, store, model, clock }) {
    this.artifactsPath = artifactsPath;
    this.store = store;
    this.model = model;
    this.clock = clock;
    this.ledgerPath = resolve(artifactsPath, 'cost-ledger.jsonl');
    this.decisionPath = resolve(artifactsPath, 'decision-log.jsonl');
    this.partialResultPath = resolve(artifactsPath, 'partial-result.md');
  }

  async run({ policy, workItems, runId, maxCalls = Infinity }) {
    let state = await this.store.read();
    if (!state) {
      state = initialState(policy, workItems, this.clock);
      await this.store.write(state);
      await this.#ledger('checkpoint-reserve-held', runId, state, { amountCredits: policy.checkpointReserveCredits });
    }
    if (state.workItemsFingerprint !== fingerprint(workItems)) {
      throw new Error('Workflow input changed; start a new run rather than resetting this budget ledger.');
    }
    if (state.terminal) {
      await this.#decision('terminal-run-blocked', runId, state, { code: state.terminal.code });
      return { status: state.terminal.code, state, execution: [] };
    }

    state.status = 'active';
    const execution = [];
    let callsThisRun = 0;
    while (state.nextWorkIndex < workItems.length) {
      if (callsThisRun >= maxCalls) {
        state.status = 'paused';
        await this.store.write(state);
        await this.#decision('run-paused', runId, state, { reason: 'max-calls-for-demo' });
        return { status: 'paused', state, execution };
      }
      const workItem = workItems[state.nextWorkIndex];
      const decision = await this.#select(workItem, runId, state);
      if (decision.stop) return this.#terminal(decision.code, runId, state, workItems, execution);

      const result = await this.model.invoke({
        workItem,
        model: state.selectedModel,
        correlationId: `${runId}-${String(state.nextWorkIndex + 1).padStart(3, '0')}`
      });
      callsThisRun += 1;
      state.ledger.spentCredits += result.actualCostCredits;
      state.ledger.estimatedCredits += result.actualCostCredits;
      state.ledger.committedCredits = state.ledger.spentCredits + state.ledger.reservedCheckpointCredits;
      state.ledger.tokens += result.tokens;
      state.ledger.modelCalls += 1;
      state.ledger.toolCalls += result.toolCalls;
      state.ledger.sourceCount += result.sourceCount;
      state.ledger.retries += result.retry ? 1 : 0;
      state.ledger.elapsedMs += workItem.elapsedMs;
      state.nextWorkIndex += 1;
      state.noProgressStreak = result.progress ? 0 : state.noProgressStreak + 1;
      if (result.progress) state.completedItems.push(workItem.id);
      execution.push({ workItem: workItem.id, model: state.selectedModel, progress: result.progress });
      await this.#ledger('model-cost-recorded', runId, state, {
        workItemId: workItem.id, model: state.selectedModel, actualCostCredits: result.actualCostCredits
      });

      if (!state.signals.warned && state.ledger.committedCredits >= state.policy.maxCredits * state.policy.warningRatio) {
        state.signals.warned = true;
        await this.#decision('budget-warning', runId, state, { threshold: state.policy.warningRatio });
      }
      await this.store.write(state);

      if (state.noProgressStreak >= state.policy.noProgressLimit) {
        return this.#terminal('NO_PROGRESS_LIMIT', runId, state, workItems, execution);
      }
      if (state.ledger.committedCredits >= state.policy.maxCredits) {
        return this.#terminal('BUDGET_EXHAUSTED', runId, state, workItems, execution);
      }
    }
    state.status = 'completed';
    await this.store.write(state);
    await this.#decision('workflow-completed', runId, state, {});
    return { status: 'completed', state, execution };
  }

  async #select(workItem, runId, state) {
    if (state.ledger.committedCredits >= state.policy.maxCredits) {
      await this.#decision('hard-cap-blocked-call', runId, state, { workItemId: workItem.id });
      return { stop: true, code: 'BUDGET_EXHAUSTED' };
    }

    const primaryCost = workItem.primaryCostCredits;
    const projectedPrimary = state.ledger.committedCredits + primaryCost;
    if (!state.signals.degraded && projectedPrimary >= state.policy.maxCredits * state.policy.degradationRatio) {
      state.signals.degraded = true;
      state.selectedModel = 'cheap';
      state.scope = 'narrow';
      await this.#decision('graceful-degradation', runId, state, {
        workItemId: workItem.id,
        projectedPrimaryCredits: projectedPrimary,
        selectedModel: 'cheap'
      });
    }

    const cost = workItem[`${state.selectedModel}CostCredits`];
    if (cost === undefined || state.ledger.committedCredits + cost > state.policy.maxCredits) {
      await this.#decision('hard-cap-blocked-call', runId, state, { workItemId: workItem.id, selectedModel: state.selectedModel });
      return { stop: true, code: 'BUDGET_EXHAUSTED' };
    }
    return { stop: false };
  }

  async #terminal(code, runId, state, workItems, execution) {
    state.status = 'terminal';
    state.terminal = { code, at: this.clock.now() };
    await this.store.write(state);
    await this.#decision('workflow-terminal', runId, state, { code });
    await writePartialResult(this.partialResultPath, state, workItems);
    return { status: code, state, execution };
  }

  async #ledger(event, runId, state, detail) {
    await appendJsonLine(this.ledgerPath, { event, runId, timestamp: this.clock.now(), ledger: state.ledger, ...detail });
  }

  async #decision(event, runId, state, detail) {
    await appendJsonLine(this.decisionPath, {
      event, runId, timestamp: this.clock.now(), status: state.status,
      committedCredits: state.ledger.committedCredits, remainingCredits: state.policy.maxCredits - state.ledger.committedCredits,
      ...detail
    });
  }
}
