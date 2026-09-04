import { FixedClock } from './clock.js';
import { FixtureMcpServer } from './fixture-mcp-server.js';
import { GrantStore } from './grant-store.js';
import { SecureMcpHost } from './mcp-host.js';
import { ProvenanceLog } from './provenance-log.js';
import { ServerRegistry } from './server-registry.js';

export function createContext({ artifactsPath, servers, toolPolicies, grants, now = '2026-09-03T12:00:00.000Z' }) {
  const clock = new FixedClock(now);
  const registry = new ServerRegistry(servers);
  const server = new FixtureMcpServer({ eventPath: `${artifactsPath}/fixture-server-events.jsonl`, clock });
  const provenance = new ProvenanceLog(`${artifactsPath}/provenance-log.jsonl`, clock);
  const host = new SecureMcpHost({
    registry,
    toolPolicies,
    grants: new GrantStore(grants),
    server,
    provenance
  });
  return { clock, registry, host };
}
