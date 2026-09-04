import { allowedDomain, allowedPath } from './constraints.js';
import { Code } from './codes.js';

function string(value) {
  return typeof value === 'string' && value.length > 0;
}

export function validateInput(toolName, input, policy) {
  if (!input || typeof input !== 'object') return Code.INPUT_SCHEMA_INVALID;
  if (toolName === 'research.lookup') {
    if (!string(input.query) || !string(input.url)) return Code.INPUT_SCHEMA_INVALID;
    return allowedDomain(input.url, policy.allowedDomains) ? Code.ACCEPTED : Code.DOMAIN_NOT_ALLOWED;
  }
  if (toolName === 'workspace.read') {
    if (!string(input.path)) return Code.INPUT_SCHEMA_INVALID;
    return allowedPath(input.path, policy.allowedRoots) ? Code.ACCEPTED : Code.PATH_NOT_ALLOWED;
  }
  if (toolName === 'cms.publish') {
    return string(input.recordId) && string(input.contentFingerprint) ? Code.ACCEPTED : Code.INPUT_SCHEMA_INVALID;
  }
  return Code.TOOL_NOT_ALLOWLISTED;
}

export function validateOutput(toolName, output, policy) {
  if (!output || typeof output !== 'object') return Code.OUTPUT_SCHEMA_INVALID;
  if (toolName === 'research.lookup') {
    return output.kind === 'evidence' && string(output.text) && string(output.sourceUrl) && allowedDomain(output.sourceUrl, policy.allowedDomains)
      ? Code.ACCEPTED : Code.OUTPUT_SCHEMA_INVALID;
  }
  if (toolName === 'workspace.read') {
    return output.kind === 'file' && string(output.content) && string(output.path) && allowedPath(output.path, policy.allowedRoots)
      ? Code.ACCEPTED : Code.OUTPUT_SCHEMA_INVALID;
  }
  if (toolName === 'cms.publish') {
    return output.kind === 'publication' && output.published === true && string(output.recordId)
      ? Code.ACCEPTED : Code.OUTPUT_SCHEMA_INVALID;
  }
  return Code.OUTPUT_SCHEMA_INVALID;
}
