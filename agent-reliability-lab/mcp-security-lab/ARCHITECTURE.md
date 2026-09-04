# Architecture

```text
agent request -> SecureMcpHost -> server allowlist / tool policy / input constraints
                     |                         |
                     | deny + redacted         +--> local fixture server
                     | provenance log                    |
                     v                                   v
             independent write approval          output schema + path/domain checks
                                                            |
                                                            v
                                                   accepted data + provenance
```

The host—not the agent prompt and not the MCP server—owns the policy decision. A tool call passes these gates in order:

1. Named server is allowlisted and not revoked.
2. Named tool is allowlisted for that server.
3. Input matches a tool-specific schema and applicable domain/path boundary.
4. Write-capable tools receive an exact, one-use approval.
5. The fixture server returns untrusted output.
6. Output matches a tool-specific schema and its path/domain boundary.
7. The host records redacted provenance and, only then, returns accepted data.

The fixture transport is deliberately protocol-shaped rather than an SDK implementation so this first lab runs entirely offline with no third-party dependency. It proves host-side controls, not MCP protocol compatibility. A production integration should use the official SDK pinned to an exact reviewed version, authenticated server identity, isolated credentials, OS/network sandboxing, and the same host-side checks.
