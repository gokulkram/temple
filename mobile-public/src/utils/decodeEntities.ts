/**
 * includes/sanitize.php's clean_html() rebuilds rich-text fields through
 * DOMDocument::saveHTML(), which HTML-encodes plain text runs - so JSON from
 * api/public.php can contain "&amp;", "&lt;", "&#039;" etc. literally.
 * React Native's <Text> has no HTML decoding, so without this those would
 * show up on screen as literal "&amp;" rather than "&". Only entities that
 * can actually appear from clean_html()'s allowlisted output are handled -
 * this is not a general-purpose HTML decoder.
 */
const NAMED_ENTITIES: Record<string, string> = {
  '&lt;': '<',
  '&gt;': '>',
  '&amp;': '&',
  '&quot;': '"',
  '&#039;': "'",
  '&nbsp;': ' ',
};

export function decodeEntities(text: string): string {
  return text
    .replace(/&lt;|&gt;|&amp;|&quot;|&#039;|&nbsp;/g, (m) => NAMED_ENTITIES[m] ?? m)
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)));
}
