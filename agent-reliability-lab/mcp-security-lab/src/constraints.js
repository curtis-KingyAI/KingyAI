import path from 'node:path';

export function allowedDomain(urlString, allowedDomains) {
  try {
    const url = new URL(urlString);
    return url.protocol === 'https:' && allowedDomains.includes(url.hostname);
  } catch {
    return false;
  }
}

export function allowedPath(pathString, roots) {
  if (typeof pathString !== 'string' || !path.posix.isAbsolute(pathString)) return false;
  const normalized = path.posix.normalize(pathString);
  return roots.some((root) => normalized === root || normalized.startsWith(`${root}/`));
}
