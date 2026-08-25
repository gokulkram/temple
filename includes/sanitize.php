<?php
/**
 * Output escaping and a strict HTML allowlist.
 *
 * The page content deliberately contains a little markup: <br> in headings,
 * <a href="tel:..."> in the contact row, <em> and <span class="time"> in the
 * visitor cards. So it cannot simply be htmlspecialchars()'d on the way out.
 *
 * Instead, anything the admin saves is parsed and rebuilt from an allowlist.
 * Tags that are not listed are removed, attributes that are not listed are
 * dropped, and link targets are restricted to safe schemes. A <script>, an
 * onclick=, or a javascript: URL cannot survive this.
 */
declare(strict_types=1);

/** Escape plain text for HTML output. Use this for everything non-rich. */
function esc(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape for use inside a single-quoted JavaScript string literal.
 *
 * JSON_HEX_TAG matters: without it a caption containing "</script>" would
 * terminate the surrounding block. JSON_UNESCAPED_SLASHES keeps image paths
 * readable ("images/x.jpg" rather than "images\/x.jpg").
 */
function esc_js(?string $v): string
{
    return substr(json_encode((string) $v,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS
        | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), 1, -1);
}

const ALLOWED_TAGS = [
    'br'     => [],
    'p'      => ['class'],
    'em'     => ['class'],
    'i'      => ['class'],
    'strong' => ['class'],
    'b'      => ['class'],
    'span'   => ['class', 'style'],
    'small'  => ['class'],
    'a'      => ['href', 'class', 'target', 'rel', 'style'],
];

const ALLOWED_SCHEMES = ['http', 'https', 'tel', 'mailto'];

/**
 * Clean a rich-text value coming out of the database (or going into it).
 * Returns markup that is safe to echo unescaped.
 */
function clean_html(?string $html): string
{
    $html = (string) $html;
    if ($html === '' || strip_tags($html) === $html) {
        // No markup at all - plain escaping is enough and much cheaper.
        //
        // This must be IDEMPOTENT: the admin sanitises on save and the page
        // sanitises again on output, so running it twice has to give the same
        // result. Hence double_encode = false (an existing &amp; is left
        // alone rather than becoming &amp;amp;) and ENT_NOQUOTES (quotes in
        // body text are not special and escaping them only adds noise).
        return htmlspecialchars($html, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    // The meta line forces UTF-8; the wrapper keeps DOMDocument from adding
    // <html><body> of its own.
    $doc->loadHTML(
        '<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $root = $doc->getElementById('__root');
    if (!$root) {
        return esc(strip_tags($html));
    }

    scrub_node($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

/** Recursively strip disallowed tags and attributes, in place. */
function scrub_node(DOMNode $node): void
{
    // Iterate over a snapshot: the live NodeList shifts as we remove things.
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) {
            continue;
        }

        if ($child instanceof DOMComment) {
            $child->parentNode->removeChild($child);
            continue;
        }

        if (!$child instanceof DOMElement) {
            $child->parentNode->removeChild($child);
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (!array_key_exists($tag, ALLOWED_TAGS)) {
            // Unwrap rather than delete, so the text inside is not lost -
            // except for tags whose content is code, which go entirely.
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $child->parentNode->removeChild($child);
                continue;
            }
            scrub_node($child);
            while ($child->firstChild) {
                $child->parentNode->insertBefore($child->firstChild, $child);
            }
            $child->parentNode->removeChild($child);
            continue;
        }

        // Drop every attribute that is not explicitly permitted for this tag.
        $allowed = ALLOWED_TAGS[$tag];
        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            if (!in_array($name, $allowed, true)) {
                $child->removeAttribute($attr->nodeName);
                continue;
            }
            if ($name === 'href' && !safe_url($attr->nodeValue)) {
                $child->removeAttribute('href');
            }
            if ($name === 'style' && preg_match('/expression|javascript:|url\s*\(/i', $attr->nodeValue)) {
                $child->removeAttribute('style');
            }
        }

        // Any link that opens a new tab must not hand over window.opener.
        if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
            $child->setAttribute('rel', 'noopener noreferrer');
        }

        scrub_node($child);
    }
}

/** True if a href is a fragment, a relative path, or an allowed scheme. */
function safe_url(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    // Reject control characters used to smuggle "java\0script:".
    if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return false;
    }
    if ($url[0] === '#' || $url[0] === '/') {
        return true;
    }
    if (!preg_match('#^([a-z][a-z0-9+.\-]*):#i', $url, $m)) {
        return true;   // relative path
    }
    return in_array(strtolower($m[1]), ALLOWED_SCHEMES, true);
}
