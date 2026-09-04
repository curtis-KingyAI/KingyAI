import { writeFile } from 'node:fs/promises';

export async function writePartialResult(path, state, workItems) {
  const skipped = workItems.slice(state.nextWorkIndex).map((item) => item.id);
  const result = [
    '# Cost-governed partial result',
    '',
    `Terminal status: **${state.terminal.code}**`,
    '',
    `Completed items: ${state.completedItems.join(', ') || 'none'}`,
    `Skipped items: ${skipped.join(', ') || 'none'}`,
    `Actual spend: ${state.ledger.spentCredits} credits`,
    `Checkpoint reserve: ${state.ledger.reservedCheckpointCredits} credits`,
    `Committed budget: ${state.ledger.committedCredits} / ${state.policy.maxCredits} credits`,
    `Model calls: ${state.ledger.modelCalls}; tool calls: ${state.ledger.toolCalls}; retries: ${state.ledger.retries}; elapsed: ${state.ledger.elapsedMs} ms`,
    '',
    'Human decision required: Review the partial evidence, then either provide a verified next source, explicitly increase the budget, or end the workflow.',
    ''
  ].join('\n');
  await writeFile(path, result);
}
