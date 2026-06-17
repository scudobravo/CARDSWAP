/**
 * In catalogo la serial è spesso salvata con "#" (es. "Kaka #33")
 * ma nel mondo TCG si legge come "/33".
 */
export function formatCardDisplayName(name) {
  if (!name) return ''
  return name.replace(/#\s*(\d+)/g, '/$1')
}
