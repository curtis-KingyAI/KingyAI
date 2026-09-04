# MCP host security decision report

| Scenario | Host result | Server output accepted? |
| --- | --- | --- |
| valid research result | ACCEPTED | yes |
| prompt injection stays evidence data | ACCEPTED | yes |
| malformed output is rejected | OUTPUT_SCHEMA_INVALID | no |
| disallowed research domain | DOMAIN_NOT_ALLOWED | no |
| path traversal attempt | PATH_NOT_ALLOWED | no |
| unallowlisted server | SERVER_NOT_ALLOWLISTED | no |
| unallowlisted tool | TOOL_NOT_ALLOWLISTED | no |
| publish without independent approval | APPROVAL_REQUIRED | no |
| publish with scoped approval | ACCEPTED | yes |
| replay approval | APPROVAL_REPLAYED | no |
| revoked server | SERVER_REVOKED | no |

Prompt-injection text in a valid research response is retained only as untrusted evidence data. It cannot alter the host allowlist, invoke another tool, access a path, or publish a record.
The provenance log stores identities, codes, and fingerprints—not raw prompts, response text, approval identifiers, or fixture nonces.
