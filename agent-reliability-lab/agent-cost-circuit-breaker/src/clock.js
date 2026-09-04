export class FixedClock {
  constructor(iso = '2026-09-03T12:00:00.000Z') {
    this.iso = iso;
  }

  now() {
    return this.iso;
  }
}
