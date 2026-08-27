import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import RichText from './RichText';
import type { VisitCard as VisitCardType } from '../types';

export default function VisitCard({ card }: { card: VisitCardType }) {
  return (
    <View style={styles.card}>
      <Text style={styles.icon}>{card.icon}</Text>
      <View style={styles.text}>
        <Text style={styles.titleTa}>{card.title_ta}</Text>
        <Text style={styles.titleEn}>{card.title_en}</Text>
        <RichText html={card.body} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    backgroundColor: '#f7f2ec',
    borderWidth: 1,
    borderColor: '#e8ddce',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    gap: 12,
  },
  icon: { fontSize: 24 },
  text: { flex: 1 },
  titleTa: { fontSize: 16, fontWeight: '700', color: '#333' },
  titleEn: { fontSize: 12, color: '#777', marginBottom: 4 },
});
