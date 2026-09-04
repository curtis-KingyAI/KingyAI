# Architecture

```text
workflow input -> stage -> fingerprinted artifact -> atomically committed checkpoint
                    ^                                  |
                    |                                  v
                 resume <---- validate input + artifact + output fingerprint
```

The workflow has six local stages:

```text
collect -> extract -> outline -> draft -> fact-check -> ready-for-review
```

`collect` fingerprints the source manifest. `extract` additionally fingerprints the source document contents. This distinction lets the Lab show targeted invalidation: changing a document's content retains the collected source list but reruns extraction and every dependent stage.

For each completed stage, `workflow-state.json` stores:

- completion status and workflow version;
- the input fingerprint used to create it;
- the output fingerprint and artifact path; and
- a deterministic completion timestamp.

On resume, a stage is reused only if its expected input fingerprint matches, its artifact exists, the stored output fingerprint matches the artifact, and hashing the artifact output again produces the same fingerprint. The first invalid checkpoint removes itself and all downstream state before recomputation.

The local write-then-rename pattern protects one file in this one-process demonstration. A production workflow requires durable transactional state, worker ownership/leases, queue semantics, and explicit reconciliation for any side effect outside the checkpoint store.
