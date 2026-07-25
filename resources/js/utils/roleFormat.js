export function formatRoleName(role) {
  const roleName = typeof role === 'string' ? role : role?.name;

  if (!roleName) return '';

  return roleName
    .replaceAll('-', ' ')
    .replace(/\b\w/g, character => character.toUpperCase())
    .replace(/\bFca\b/g, 'FCA');
}
