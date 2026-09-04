# Permission-gate decision report

| Scenario | Decision | Reason |
| --- | --- | --- |
| collect an official source | allow | ALLOWED |
| create a draft | allow | ALLOWED |
| direct publish from hostile prompt | deny | APPROVAL_REQUIRED |
| read unrelated private fixture | deny | PERMISSION_DENIED |
| publish with one-use approval | allow | ALLOWED |
| replay the consumed approval | deny | APPROVAL_REPLAYED |
| substitute another target | deny | APPROVAL_SCOPE_MISMATCH |
| use expired approval | deny | APPROVAL_EXPIRED |

The prompt text supplied to the gateway is deliberately not used to authorize actions and is not written to the audit log.
