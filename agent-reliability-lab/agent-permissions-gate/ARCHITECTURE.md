# Architecture

```text
hostile or ordinary agent request
            |
            v
     ActionGateway (policy enforcement point)
       |          |             |
       |          |             +--> validates one-use approval grant for publish
       |          +--> append-only, redacted audit event for every decision
       v
  MockServices (local source fixture and mock CMS)
```

The gateway receives `actor`, `action`, `resource`, a correlation ID, and optionally an approval-grant ID. It does not treat the prompt as authority. `src/policy.js` first finds the actor's action/resource rule, then validates a grant only when the rule requires one.

The demo policy permits:

| Action | Resource | Decision |
| --- | --- | --- |
| `source.collect` | `source://official/*` | allow |
| `cms.draft.create` | `cms://drafts/launch-aurora` | allow |
| `cms.publish` | `cms://records/*` | require an exact one-use grant |
| any other action | any resource | deny |

The wildcard on the publish policy only means an approval may be requested for a mocked record. The grant itself is exact: a grant for `launch-aurora` cannot publish `launch-borealis`.

The local store intentionally illustrates data flow. In production, the enforcement point would call a durable policy/identity system and the grant consume + external action would need transaction/compensation semantics.
