import { Code } from './codes.js';

export class GrantStore {
  constructor(grants) {
    this.grants = new Map(grants.map((grant) => [grant.id, { ...grant, used: false }]));
  }

  validate(id, scope) {
    const grant = this.grants.get(id);
    if (!grant) return Code.APPROVAL_REQUIRED;
    if (grant.used) return Code.APPROVAL_REPLAYED;
    if (grant.serverId !== scope.serverId || grant.toolName !== scope.toolName || grant.resource !== scope.resource) {
      return Code.APPROVAL_SCOPE_MISMATCH;
    }
    return Code.ACCEPTED;
  }

  consume(id) {
    this.grants.get(id).used = true;
  }
}
