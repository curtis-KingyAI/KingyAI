# Threat model

## Asset and safety objective

The protected assets are the host's tool authority, permitted file boundary, permitted research domains, mock publication action, and the integrity of provenance evidence. The objective is to prevent untrusted server metadata/output from expanding tool authority or causing an unapproved side effect.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Agent request | Untrusted request for tool use | Server/tool policy and independent constraints. |
| Fixture server identity | Allowlisted only when named and active | Registry allowlist and revocation flag. |
| Tool input | Untrusted parameter data | Tool-specific schema, domain, and path checks. |
| Tool output and text | Untrusted data, including prompt injection | Output schema validation; no output is interpreted as host policy. |
| Write action | Consequential side effect | One-use grant separate from the server recommendation. |
| Provenance log | Evidence output | Fixed redacted schema with fingerprints only. |

## Attacks demonstrated

- Prompt injection contained in a valid research-text field.
- An unallowlisted server and unallowlisted tool name.
- Malformed output that fails schema validation.
- Domain escape to `evil.example`.
- Path traversal toward an SSH key path.
- Publish call without approval and approval replay.
- Server revocation after prior validity.

## Non-goals and residual risk

- This is not an MCP protocol implementation, SDK conformance test, transport-security test, or review of a real server.
- There is no process sandbox, network firewall, filesystem sandbox, subprocess environment, OAuth flow, secret manager, certificate pinning, or signed server identity.
- A valid schema does not establish truthfulness, usefulness, or safety of the content.
- Local grants are fixtures, not cryptographic credentials.

Production deployments need an exact-version-pinned official SDK, authenticated transport and server identity, secret isolation, sandboxing, outbound controls, durable audit storage, fine-grained authorization, and a tested revocation plan.
