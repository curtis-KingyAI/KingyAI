export class ServerRegistry {
  constructor(servers) {
    this.servers = new Map(servers.map((server) => [server.id, { ...server, revoked: false }]));
  }

  lookup(serverId) {
    return this.servers.get(serverId);
  }

  revoke(serverId) {
    const server = this.servers.get(serverId);
    if (!server) throw new Error(`Unknown server: ${serverId}`);
    server.revoked = true;
  }
}
