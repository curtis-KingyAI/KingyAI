import { Decision, ReasonCode } from './constants.js';

function resourceMatches(pattern, resource) {
  if (pattern.endsWith('*')) return resource.startsWith(pattern.slice(0, -1));
  return pattern === resource;
}

function ruleFor(rules, action, resource) {
  const actionRules = rules.filter((rule) => rule.action === action);
  return {
    actionExists: actionRules.length > 0,
    rule: actionRules.find((rule) => resourceMatches(rule.resource, resource))
  };
}

export function evaluatePolicy({ policy, grants, clock, request }) {
  const principal = policy.principals[request.actor];
  if (!principal) return { decision: Decision.DENY, reasonCode: ReasonCode.PERMISSION_DENIED };

  const allowed = ruleFor(principal.allow, request.action, request.resource);
  if (allowed.rule) return { decision: Decision.ALLOW, reasonCode: ReasonCode.ALLOWED };

  const approval = ruleFor(principal.approvalRequired, request.action, request.resource);
  if (!approval.actionExists && !allowed.actionExists) {
    return { decision: Decision.DENY, reasonCode: ReasonCode.PERMISSION_DENIED };
  }
  if (!approval.rule && !allowed.rule) {
    return { decision: Decision.DENY, reasonCode: ReasonCode.RESOURCE_DENIED };
  }

  const grant = grants.find(request.approvalGrantId);
  if (!grant) return { decision: Decision.DENY, reasonCode: ReasonCode.APPROVAL_REQUIRED };
  if (grant.used) return { decision: Decision.DENY, reasonCode: ReasonCode.APPROVAL_REPLAYED };
  if (grant.expiresAt <= clock.now()) {
    return { decision: Decision.DENY, reasonCode: ReasonCode.APPROVAL_EXPIRED };
  }
  if (grant.principal !== request.actor || grant.action !== request.action || grant.resource !== request.resource) {
    return { decision: Decision.DENY, reasonCode: ReasonCode.APPROVAL_SCOPE_MISMATCH };
  }

  return { decision: Decision.ALLOW, reasonCode: ReasonCode.ALLOWED, consumeGrantId: grant.id };
}
