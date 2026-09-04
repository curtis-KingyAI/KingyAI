export class GrantStore {
  #grants;

  constructor(grants) {
    this.#grants = new Map(grants.map((grant) => [grant.id, { ...grant, used: false }]));
  }

  find(id) {
    return this.#grants.get(id);
  }

  consume(id) {
    const grant = this.#grants.get(id);
    if (!grant) throw new Error(`Unknown approval grant: ${id}`);
    grant.used = true;
  }

  snapshot() {
    return [...this.#grants.values()].map(({ nonce, ...grant }) => grant);
  }
}
