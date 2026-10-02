# Native account browser failure

The real local cookie/nonce account save succeeded. Its project-list response exposed the database revision as the string `"1"`, while the save response used integer `1`. The browser acceptance assertion failed on this schema inconsistency. No authentication was mocked and no cookie values were logged.

Targeted repair: normalize project-list revisions to JSON integers. Retain the integer assertion and rerun the native account journey. This does not alter ownership or revision conflict enforcement.
