import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { SectionHeading } from '../types';

export default function SectionHeader({ heading }: { heading?: SectionHeading }) {
  if (!heading) return null;
  return (
    <View style={styles.wrap}>
      <Text style={styles.ta}>{heading.title_ta}</Text>
      <Text style={styles.en}>{heading.title_en}</Text>
      <View style={styles.rule} />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 16, alignItems: 'center' },
  ta: { fontSize: 22, fontWeight: '700', color: '#8B1A1A', textAlign: 'center' },
  en: { fontSize: 14, color: '#777', marginTop: 2, textAlign: 'center' },
  rule: { width: 40, height: 3, backgroundColor: '#8B1A1A', borderRadius: 2, marginTop: 10 },
});
