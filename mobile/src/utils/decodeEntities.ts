/**
 * A few `hint` strings in includes/collections.php are pre-escaped
 * (e.g. "Use &lt;br&gt; for a second line") because admin/_layout.php's
 * field() echoes them unescaped into HTML - the browser decodes the
 * entities back to literal "<br>" text on display. React Native's <Text>
 * has no such decoding, so without this the hint would literally show
 * "&lt;br&gt;". Only the small set of entities collections.php actually
 * uses are handled - this is not a general-purpose HTML decoder.
 */
const ENTITIES: Record<string, string> = {
  '&lt;': '<',
  '&gt;': '>',
  '&amp;': '&',
  '&quot;': '"',
  '&#039;': "'",
};

export function decodeEntities(text: string): string {
  return text.replace(/&lt;|&gt;|&amp;|&quot;|&#039;/g, (m) => ENTITIES[m] ?? m);
}
