# KAPI pull-request governance v1

## Current status: fail closed, not activated

KAPI's review positioning is exactly **Operator-reviewed**. This repository does
not claim a stronger review boundary, autonomous review, or verified separation
from the personal-repository owner. The owner retains administrative and
recovery authority. Automation that can use the owner's credentials is inside
the same authority boundary and cannot prove human/automation separation.

`policy-v1.json` intentionally uses
`blocked_pending_external_verifier_and_human_authorizer`. Until both prerequisites
below are satisfied, every observed ready-for-review transition is denied with a
visible failed run, audit record, and denial label, including a transition
attributed to the repository owner. A human operator must return an unauthorized
ready pull request to draft; repository automation does not claim that capability.

Do not change `activation_state` to `active` merely to make a workflow green.

## Mandatory identity boundary

The account that authorizes readiness must be a separate, named natural-person
identity. It must not be the repository owner identity currently available to
automation, a bot, a shared account, an AI persona, or any account with write
access to this repository.

Before activation:

1. Create or select a named human GitHub account with public/read-only access.
2. Require MFA/passkey protection and keep its browser session, recovery
   material, token, and signing material outside GitHub Actions, Codex, agents,
   repository secrets, and the owner automation environment.
3. Record its numeric GitHub actor ID in `human_authorizer_actor_ids`.
4. Confirm it does not occur in `allowed_ready_actor_ids` or
   `automation_actor_ids`.
5. Set `credential_separation_attested` to `true` only after that separation has
   been checked outside this repository.

This separation is a fail-closed authorization control. It does not change
KAPI's **Operator-reviewed** positioning or eliminate the owner's administrative
recovery power.

## Sanitized owner credential boundary

The detailed owner credential inventory is intentionally local and ignored by
Git. No secret values, authentication challenges, cookies, private keys, token
fingerprints, or recovery material belong in this repository.

| Access class | Repository-safe conclusion |
| --- | --- |
| Owner browser and recovery access | Owner-controlled and recovery-capable; session and recovery material are not inventoried here. |
| GitHub CLI and HTTPS Git access | Owner-bound interactive access; not evidence of separation from automation. |
| OAuth grants and GitHub Apps | Installation or authorization state must be verified outside the repository; no App credential is accepted by these workflows. |
| GitHub Actions token | Ephemeral per run and limited by explicit workflow/job permissions. Only the guard receives `pull-requests: write`. |
| PATs, deploy keys, SSH keys, and repository/environment secrets | Not required by KAPI governance and prohibited from all approved workflows. Their account-level presence is not asserted here. |
| Automation identities | Any tool using the owner identity is classified as owner-bound automation, not a distinct reviewer. |

Unknown or stale account-level state must remain unknown. A local inventory may
record metadata such as class, purpose, custody boundary, status, and AI
accessibility, but must never contain credential material.

The ready transition may still be performed by an ID in
`allowed_ready_actor_ids`; that actor cannot authorize itself. A valid comment
from the separate human authorizer is required for the exact transition.

## Mandatory production check boundary

The always-emitted Actions job is named
`kapi-governance-actions-advisory-v1`. It is diagnostic only. It must **not** be
the production merge assurance required by the main-branch ruleset.

GitHub required status checks bind a workflow check by job/check name and App
source, not by workflow file, trigger, or matrix. All repository Actions jobs
share GitHub Actions App integration ID `15368`. Another workflow can therefore
imitate any Actions-owned context name.

Production activation requires a minimal GitHub App or equivalent external
verifier that:

- has its private key and runtime outside this repository, GitHub Actions, and
  Codex;
- evaluates the exact PR head SHA and the governance policy;
- is the only approved integration configured to emit
  `kapi-governance-external-v1` (repository scanning forbids that name in
  Actions, while the ruleset's exact App binding supplies the real boundary);
- has a stable positive integration ID recorded in
  `dedicated_verifier_integration_id`;
- is explicitly selected as the expected source for that check in the active
  main ruleset.

Never set the dedicated verifier ID to `15368`. Set
`dedicated_verifier_attested` to `true` only after a pilot check proves the
ruleset is bound to the external App, not merely to a matching name.

## One-use authorization protocol

After activation, the named human authorizer generates a marker from a trusted
main checkout:

```sh
python3 .github/scripts/kapi_governance.py authorize-ready \
  --pr-number 4 \
  --base-sha EXACT_40_HEX_BASE_SHA \
  --head-sha EXACT_40_HEX_HEAD_SHA \
  --authorizer-actor-id HUMAN_NUMERIC_ID \
  --ready-actor-id READY_ACTOR_NUMERIC_ID
```

The human posts that exact marker as a PR comment from the segregated account.
The evidence contains repository ID, PR number, exact base and head SHAs, the
trusted policy hash, the complete protected-file manifest hash, separate
authorizer ID, ready actor ID, 32-byte-hex nonce, and an expiry no more than five
minutes after the GitHub comment timestamp. Edited comments are invalid.

The specified ready actor then marks the same unchanged PR ready before expiry.
The metadata guard serializes events per PR and:

1. reads the current PR and all comments from GitHub;
2. verifies the exact current/event/evidence base and head SHAs;
3. verifies the trusted policy and protected-file manifest hashes;
4. verifies stable actor IDs and the human/automation separation;
5. rejects stale, expired, edited, ambiguous, or previously consumed evidence;
6. appends a consumed audit comment before allowing ready state;
7. confirms the consumed record is observable;
8. appends and confirms a denied record on any failure, applies the existing
   `invalid` label, and emits a failing result that requires manual operator
   restoration if the pull request remains ready.

The event fingerprint makes a duplicate delivery idempotent. Reusing the same
comment, nonce, or evidence hash for a later ready event is a replay and is
denied.

## Trusted execution and fork handling

The privileged guard uses `pull_request_target` so fork PRs can be evaluated and
audited with the base repository token. It checks out only
`github.event.pull_request.base.sha` with persisted credentials disabled and
executes only scripts from that trusted checkout. It never checks out, imports,
builds, or executes the PR head.

The Actions advisory uses a read-only `pull_request` token. It checks out the
base governance implementation into `.kapi-trusted`, checks out the PR into
`.kapi-candidate` as inert scan data, and executes only the base scanner and
base tests. Candidate governance executables are byte-compared to the trusted
base and fail the advisory if changed. This advisory is useful evidence but is
not a non-spoofable production merge gate.

## Missed-event reconciliation

GitHub suppresses most workflow events caused by a repository `GITHUB_TOKEN`.
A ready transition made that way may not start the `ready_for_review` workflow.
The guard therefore also runs a metadata-only reconciliation every five minutes
and through `workflow_dispatch`.

Reconciliation inspects every open non-draft PR and its stage timeline. It only
accepts a transition that already has a consumed audit record for the exact
event fingerprint, current base/head pair, policy hash, and protected-file
manifest. It never consumes a fresh authorization on behalf of a missed event.
Missing evidence, changed bases or heads, suppressed events, hash mismatches, or
inactive policy cause a confirmed denial record, an `invalid` label, and a
visible failed run. Reconciliation is detection and evidence; a human operator
must restore draft state.

## Draft-restoration capability boundary

`draft_restoration_mode` is pinned to `manual_operator`. In smoke run
`29634648786` (job `88054725374`), the trusted guard had `pull-requests: write`,
completed its trusted checkout, executable-SHA proof, manifest validation,
policy denial, label write, and audit comment, but GitHub rejected the
`convertPullRequestToDraft` GraphQL mutation for the repository `GITHUB_TOKEN`
with `FORBIDDEN — Resource not accessible by integration`.

The guard therefore does not call a draft mutation and never reports an
unauthorized transition as reverted. A denied ready PR remains visibly failed
and labeled `invalid` until a human operator returns it to draft or otherwise
resolves it. A human operator must restore draft state when the PR remains
ready. The operator records the exact base/head SHAs, denial run and audit
IDs, restoration timestamp, and operator identity. This manual recovery is not
an independent reviewer or credential-separation control.

KAPI remains blocked until the separately hosted verifier emits
`kapi-governance-external-v1` from its exact ruleset-bound integration. That
external required check, not draft state and not an Actions-owned check name,
is the intended fail-closed merge boundary.

## Append-only record limitation

The implementation appends new consumed/denied comments and never edits an
existing authorization or audit record. GitHub comments are not immutable:
repository administrators can delete them. If tamper-evident or regulated
retention is required, the external verifier must also copy event payloads and
comment hashes to write-once storage outside GitHub.

## One-time PR #4 bootstrap

PR #3 is a technical **NO-GO**. Keep it draft and unmerged. Do not merge it,
cherry-pick it, or use any of its 108-file payload as a prerequisite for this
governance change.

The trusted scripts do not yet exist on `main`. Consequently, this PR's
versioned Actions advisory correctly fails rather than executing its own
candidate copy. PR #4 carries a separate, read-only legacy `verify` workflow
solely to satisfy the repository's pre-existing check during bootstrap. That
workflow has no path filter and has two modes:

- On PR #4 against the pinned pre-governance main SHA, Actions necessarily
  executes the candidate workflow YAML. That reviewed YAML checks exact
  base/head metadata and emits SHA-256 hashes for manual comparison; it executes
  no other candidate file or script.
- After PR #4 lands, it executes the scanner and tests only from the checked-out
  base SHA and treats the PR checkout as inert scan data.

GitHub provides no cryptographic workflow identity for an Actions check. The
`verify` name plus GitHub Actions App ID `15368` cannot prove which workflow or
trigger produced it. PR #4 therefore has an explicit, one-time manual bootstrap
weakness until the dedicated external App is live. A green `verify` check by
itself is not approval.

Use this PR #4-only sequence:

1. Keep the policy blocked and keep PR #3 draft/unmerged. Fetch PR #4's exact
   head into a clean local checkout. Record the 40-character head SHA, the
   SHA-256 of `.github/workflows/kapi.yml`, and a SHA-256 manifest of every
   changed governance file.
2. A named operator reviews the complete diff and confirms the PR #4 head is
   based directly on pinned main SHA
   `6070f10c9ab5611c0966056a43eb24ae6beda7ce`. Any rebase or head change voids
   the bootstrap review and requires a fresh full review.
3. From that exact fetched head, run both commands under "Local verification"
   below and retain the complete logs. Confirm the scanner and all tests pass;
   do not substitute the Actions job for this local execution.
4. Inspect the raw `verify` check-run metadata and complete job log. Bind the run
   to PR `4` and the recorded exact head SHA. Confirm the log reports
   `KAPI_VERIFY_MODE=manual-pr4-bootstrap`, the pinned base SHA, the same
   workflow hash, and the same file manifest. Confirm the commands came from
   that exact reviewed candidate workflow YAML and that they only hashed all
   other PR files rather than executing any candidate script.
5. Search all check runs at that SHA for another job named `verify`. Treat any
   collision, unexpected rerun, different head SHA, different manifest, skipped
   step, or unavailable log as a hard failure. Record the run URL/ID, attempt,
   GitHub Actions App ID, head SHA, workflow hash, manifest hash, local test-log
   hash, operator identity, and review time in the change record. This is
   operator review, not a claim of a stronger review boundary.
6. Merge PR #4 only through the existing no-bypass ruleset after that named
   operator signs the one-time record. The candidate workflow YAML is manually
   trusted for this one merge because App ID `15368` cannot cryptographically
   distinguish the workflow file.
7. On a subsequent harmless PR, confirm both Actions jobs execute scanner/tests
   from the base checkout and report their expected contexts. Candidate code
   must remain inert. Keep Actions checks advisory/legacy, not production
   governance assurance.
8. Deploy and pilot the separate external verifier. Configure the main ruleset
   to require `kapi-governance-external-v1` from that verifier's exact App ID.
   Do not activate policy or remove the manual restriction before this binding
   is proven.
9. Configure the separate human authorizer ID and attest both prerequisites in
   a policy-only PR. Activate only after the external check, fork denial and
   audit, one-use authorization, replay rejection, scheduled reconciliation,
   and manual-recovery tests all pass on GitHub.

Executable governance changes after bootstrap should use a new version and be
evaluated by the external verifier. The Actions advisory deliberately fails if
a PR changes its own guard/scanner/test executable bytes; it must never bless
the code that is currently defining the check.

## Local verification

```sh
PYTHONDONTWRITEBYTECODE=1 python3 .github/scripts/kapi_governance.py scan-workflows --root .
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s .github/tests -v
```
