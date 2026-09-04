# Architecture

```text
frozen fixture + source snapshots -> deterministic candidate strategy -> hard evaluator
                                                                          |
                         per-case trace <--------------------------------+
                                                                          |
                                           scorecard / review packet / release gate
```

Each fixture defines the source snapshots available to the draft, allowed claim IDs, valid citation/source-date pairs, claim boundary, required limitations, disclosure/uncertainty rules, duplicate-content rule, and whether escalation is required.

The hard evaluator never reads the prose as authority. It compares structured candidate fields to the fixture. A candidate becomes `ready-for-review` only when every hard rule passes. A correct escalation becomes `escalate`; it is a safe handoff, not a public draft. Any other failure is `blocked`.

The `weak` and `constrained` strategies are deterministic local stand-ins for a changed or constrained model/prompt. They exist to make regression behavior visible without calling a model. A future optional judge may assess subjective clarity, but its result must remain separate from—and unable to override—the hard gate.
