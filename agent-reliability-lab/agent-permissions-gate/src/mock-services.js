export class MockServices {
  #records = new Map([
    ['launch-aurora', { id: 'launch-aurora', status: 'draft', publishCount: 0 }],
    ['launch-borealis', { id: 'launch-borealis', status: 'draft', publishCount: 0 }]
  ]);

  async execute(request) {
    if (request.action === 'source.collect') {
      return { sources: ['https://example.invalid/aurora/official-announcement'] };
    }
    if (request.action === 'cms.draft.create') {
      return { draftId: 'launch-aurora', status: 'draft-created' };
    }
    if (request.action === 'cms.publish') {
      const recordId = request.resource.split('/').at(-1);
      const record = this.#records.get(recordId);
      if (!record) throw new Error(`Unknown mocked record: ${recordId}`);
      record.status = 'published';
      record.publishCount += 1;
      return { recordId, status: record.status, publishCount: record.publishCount };
    }
    throw new Error(`A denied action reached the mock service: ${request.action}`);
  }

  snapshot() {
    return [...this.#records.values()];
  }
}
