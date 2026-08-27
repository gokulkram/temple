import React from 'react';
import { Linking, StyleSheet, Text, type StyleProp, type TextStyle } from 'react-native';
import { decodeEntities } from '../utils/decodeEntities';

/**
 * Renders the small, fixed HTML allowlist includes/sanitize.php guarantees
 * for "rich" fields: br, p, em, i, strong, b, span[class], small,
 * a[href,target,rel] - always well-formed (it comes from
 * DOMDocument::saveHTML()). A hand-rolled tokenizer is enough; there is no
 * nesting depth, tables, lists, or embedded media to support, so a full
 * HTML/CSS engine (e.g. react-native-render-html) would be solving a
 * generality problem this content never has.
 *
 * Renders into ONE nested <Text> tree (never <View>) so inline flow - bold
 * or a link in the middle of a sentence - wraps correctly, exactly like a
 * browser would render the same markup inline.
 */

type Node =
  | { type: 'text'; text: string }
  | { type: 'element'; tag: string; attrs: Record<string, string>; children: Node[] };

const VOID_TAGS = new Set(['br']);
const TOKEN_RE = /<(\/?)([a-zA-Z]+)([^>]*)>/g;

function parseAttrs(attrString: string): Record<string, string> {
  const attrs: Record<string, string> = {};
  const attrRe = /([a-zA-Z-]+)\s*=\s*"([^"]*)"|([a-zA-Z-]+)\s*=\s*'([^']*)'/g;
  let m: RegExpExecArray | null;
  while ((m = attrRe.exec(attrString))) {
    const name = (m[1] ?? m[3])?.toLowerCase();
    const value = m[2] ?? m[4] ?? '';
    if (name) {
      attrs[name] = value;
    }
  }
  return attrs;
}

function parse(html: string): Node[] {
  const root: Node[] = [];
  const stack: Array<{ tag: string; children: Node[] }> = [{ tag: '__root', children: root }];

  let lastIndex = 0;
  let match: RegExpExecArray | null;
  TOKEN_RE.lastIndex = 0;

  const pushText = (raw: string) => {
    if (raw === '') return;
    stack[stack.length - 1].children.push({ type: 'text', text: decodeEntities(raw) });
  };

  while ((match = TOKEN_RE.exec(html))) {
    pushText(html.slice(lastIndex, match.index));
    lastIndex = TOKEN_RE.lastIndex;

    const [, closing, rawTag, attrString] = match;
    const tag = rawTag.toLowerCase();

    if (closing) {
      // Pop up to (and including) the matching open tag; lenient about
      // mismatches since this content is guaranteed well-formed anyway.
      for (let i = stack.length - 1; i > 0; i--) {
        if (stack[i].tag === tag) {
          stack.length = i;
          break;
        }
      }
      continue;
    }

    if (VOID_TAGS.has(tag)) {
      stack[stack.length - 1].children.push({ type: 'element', tag, attrs: {}, children: [] });
      continue;
    }

    const node: Node = { type: 'element', tag, attrs: parseAttrs(attrString), children: [] };
    stack[stack.length - 1].children.push(node);
    stack.push({ tag, children: node.children });
  }

  pushText(html.slice(lastIndex));
  return root;
}

function resolveHref(href: string | undefined): string | null {
  if (!href) return null;
  if (/^(https?:|tel:|mailto:)/i.test(href)) return href;
  return null; // relative paths / '#' fragments have no target in a native app
}

function renderNodes(nodes: Node[], keyPrefix: string): React.ReactNode[] {
  return nodes.map((node, i) => renderNode(node, `${keyPrefix}-${i}`));
}

function renderNode(node: Node, key: string): React.ReactNode {
  if (node.type === 'text') {
    return node.text;
  }

  const children = renderNodes(node.children, key);

  switch (node.tag) {
    case 'br':
      return '\n';
    case 'p':
      return (
        <Text key={key}>
          {children}
          {'\n\n'}
        </Text>
      );
    case 'em':
    case 'i':
      return (
        <Text key={key} style={styles.italic}>
          {children}
        </Text>
      );
    case 'strong':
    case 'b':
      return (
        <Text key={key} style={styles.bold}>
          {children}
        </Text>
      );
    case 'small':
      return (
        <Text key={key} style={styles.small}>
          {children}
        </Text>
      );
    case 'span':
      return (
        <Text key={key} style={node.attrs.class === 'time' ? styles.time : undefined}>
          {children}
        </Text>
      );
    case 'a': {
      const href = resolveHref(node.attrs.href);
      return (
        <Text
          key={key}
          style={styles.link}
          onPress={href ? () => Linking.openURL(href) : undefined}
        >
          {children}
        </Text>
      );
    }
    default:
      // Any tag outside the allowlist should never reach here (the server
      // already stripped it), but unwrap defensively rather than drop text.
      return <Text key={key}>{children}</Text>;
  }
}

export default function RichText({
  html,
  style,
}: {
  html: string;
  style?: StyleProp<TextStyle>;
}) {
  if (!html) return null;
  const tree = parse(html);
  return <Text style={[styles.base, style]}>{renderNodes(tree, 'n')}</Text>;
}

const styles = StyleSheet.create({
  base: {
    fontSize: 15,
    lineHeight: 22,
    color: '#333',
  },
  italic: { fontStyle: 'italic' },
  bold: { fontWeight: '700' },
  small: { fontSize: 12, color: '#777' },
  time: { color: '#8B1A1A', fontWeight: '600' },
  link: { color: '#8B1A1A', textDecorationLine: 'underline' },
});
