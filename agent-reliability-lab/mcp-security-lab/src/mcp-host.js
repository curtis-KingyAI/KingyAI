import { Code } from './codes.js';
import { fingerprint } from './hash.js';
import { validateInput, validateOutput } from './schemas.js';

export class SecureMcpHost {
  constructor({ registry, toolPolicies, grants, server, provenance }) {
    this.registry = registry;
    this.toolPolicies = toolPolicies;
    this.grants = grants;
    this.server = server;
    this.provenance = provenance;
  }

  async call(request) {
    const inputFingerprint = fingerprint(request.input ?? null);
    const serverRecord = this.registry.lookup(request.serverId);
    if (!serverRecord) return this.#reject(request, Code.SERVER_NOT_ALLOWLISTED, inputFingerprint);
    if (serverRecord.revoked) return this.#reject(request, Code.SERVER_REVOKED, inputFingerprint);
    const policy = this.toolPolicies[request.serverId]?.[request.toolName];
    if (!policy) return this.#reject(request, Code.TOOL_NOT_ALLOWLISTED, inputFingerprint);

    const inputCode = validateInput(request.toolName, request.input, policy);
    if (inputCode !== Code.ACCEPTED) return this.#reject(request, inputCode, inputFingerprint);

    if (policy.writeCapable) {
      const approvalCode = this.grants.validate(request.approvalGrantId, {
        serverId: request.serverId,
        toolName: request.toolName,
        resource: `cms://records/${request.input.recordId}`
      });
      if (approvalCode !== Code.ACCEPTED) return this.#reject(request, approvalCode, inputFingerprint);
    }

    const output = await this.server.invoke(request);
    const outputCode = validateOutput(request.toolName, output, policy);
    const outputFingerprint = fingerprint(output);
    if (outputCode !== Code.ACCEPTED) return this.#reject(request, outputCode, inputFingerprint, outputFingerprint);
    if (policy.writeCapable) this.grants.consume(request.approvalGrantId);
    await this.provenance.record({
      correlationId: request.correlationId, serverId: request.serverId, toolName: request.toolName,
      code: Code.ACCEPTED, inputFingerprint, outputFingerprint
    });
    return {
      accepted: true,
      code: Code.ACCEPTED,
      data: output,
      provenance: { serverId: request.serverId, toolName: request.toolName, outputFingerprint }
    };
  }

  async #reject(request, code, inputFingerprint, outputFingerprint = null) {
    await this.provenance.record({
      correlationId: request.correlationId, serverId: request.serverId, toolName: request.toolName,
      code, inputFingerprint, outputFingerprint
    });
    return { accepted: false, code, data: null };
  }
}
