import { AuditLog } from './audit-log.js';
import { FixedClock } from './clock.js';
import { GrantStore } from './grant-store.js';
import { MockServices } from './mock-services.js';
import { ActionGateway } from './action-gateway.js';

export function createContext({ policy, grants, auditPath, now }) {
  const clock = new FixedClock(now);
  const grantStore = new GrantStore(grants);
  const services = new MockServices();
  const gateway = new ActionGateway({
    policy,
    grants: grantStore,
    clock,
    auditLog: new AuditLog(auditPath),
    services
  });
  return { clock, grantStore, services, gateway };
}
