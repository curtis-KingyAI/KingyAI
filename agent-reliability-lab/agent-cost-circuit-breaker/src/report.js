import { writeFile } from 'node:fs/promises';

export async function writeCostReport(path, hardBudget, noProgress, continuity) {
  const report = [
    '# Cost circuit-breaker report',
    '',
    '| Scenario | Terminal status | Model calls | Actual spend | Committed budget |',
    '| --- | --- | --- | --- | --- |',
    `| Hard budget | ${hardBudget.status} | ${hardBudget.state.ledger.modelCalls} | ${hardBudget.state.ledger.spentCredits} | ${hardBudget.state.ledger.committedCredits} / ${hardBudget.state.policy.maxCredits} |`,
    `| No progress | ${noProgress.status} | ${noProgress.state.ledger.modelCalls} | ${noProgress.state.ledger.spentCredits} | ${noProgress.state.ledger.committedCredits} / ${noProgress.state.policy.maxCredits} |`,
    `| Resume continuity | ${continuity.status} | ${continuity.state.ledger.modelCalls} | ${continuity.state.ledger.spentCredits} | ${continuity.state.ledger.committedCredits} / ${continuity.state.policy.maxCredits} |`,
    '',
    'Hard-budget flow: warning fires at 70% committed budget; a cheaper model and narrowed scope are selected at the 85% projected threshold; the workflow checkpoints and stops at the 100% committed cap.',
    'No-progress flow: two consecutive no-progress results stop the workflow below the financial cap.',
    'Resume continuity: a fresh context reads the original persisted ledger and continues with its remaining allowance rather than a new budget.',
    ''
  ].join('\n');
  await writeFile(path, report);
}
