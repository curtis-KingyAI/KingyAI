import { evaluatePolicy } from './policy.js';

export class ActionGateway {
  constructor({ policy, grants, clock, auditLog, services }) {
    this.policy = policy;
    this.grants = grants;
    this.clock = clock;
    this.auditLog = auditLog;
    this.services = services;
  }

  async execute(request) {
    const policyResult = evaluatePolicy({
      policy: this.policy,
      grants: this.grants,
      clock: this.clock,
      request
    });

    const event = {
      timestamp: this.clock.now(),
      correlationId: request.correlationId,
      actor: request.actor,
      action: request.action,
      resource: request.resource,
      decision: policyResult.decision,
      reasonCode: policyResult.reasonCode
    };

    if (policyResult.decision === 'deny') {
      await this.auditLog.record(event);
      return { ...policyResult, result: null };
    }

    const result = await this.services.execute(request);
    if (policyResult.consumeGrantId) this.grants.consume(policyResult.consumeGrantId);
    await this.auditLog.record(event);
    return { ...policyResult, result };
  }
}
