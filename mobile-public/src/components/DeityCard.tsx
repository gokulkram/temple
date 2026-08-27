import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import RichText from './RichText';
import type { DeityCard as DeityCardType } from '../types';

export default function DeityCard({ deity }: { deity: DeityCardType }) {
  return (
    <View style={styles.card}>
      <Text style={styles.icon}>{deity.icon}</Text>
      <Text style={styles.nameTa}>{deity.name_ta}</Text>
      <Text style={styles.nameEn}>{deity.name_en}</Text>
      <RichText html={deity.desc_ta} style={styles.descTa} />
      {deity.desc_en ? <RichText html={deity.desc_en} style={styles.descEn} /> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#f7f2ec',
    borderWidth: 1,
    borderColor: '#e8ddce',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  icon: { fontSize: 32, textAlign: 'center', marginBottom: 6 },
  nameTa: { fontSize: 18, fontWeight: '700', color: '#8B1A1A', textAlign: 'center' },
  nameEn: { fontSize: 13, color: '#777', textAlign: 'center', marginBottom: 8 },
  descTa: { textAlign: 'center' },
  descEn: { textAlign: 'center', marginTop: 4, color: '#666' },
});
