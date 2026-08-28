import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import RichText from './RichText';
import type { SpecialDayItem } from '../types';

export default function SpecialDayCard({ item }: { item: SpecialDayItem }) {
  return (
    <View style={styles.row}>
      <View style={styles.month}>
        <Text style={styles.monthTa}>{item.month_ta}</Text>
        <Text style={styles.monthEn}>{item.month_en}</Text>
        {item.date_label ? <Text style={styles.date}>{item.date_label}</Text> : null}
      </View>
      <View style={styles.body}>
        <Text style={styles.titleTa}>{item.title_ta}</Text>
        <Text style={styles.titleEn}>{item.title_en}</Text>
        {item.body_ta ? <RichText html={item.body_ta} /> : null}
        {item.body_en ? <RichText html={item.body_en} style={styles.bodyEn} /> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', marginBottom: 16, gap: 12 },
  month: { width: 72, alignItems: 'center' },
  monthTa: { fontSize: 14, fontWeight: '700', color: '#8B1A1A', textAlign: 'center' },
  monthEn: { textAlign: 'center', fontSize: 12 },
  date: { textAlign: 'center', fontSize: 11, color: '#999', marginTop: 2 },
  body: { flex: 1 },
  titleTa: { fontSize: 16, fontWeight: '700', color: '#333' },
  titleEn: { fontSize: 13, color: '#777', marginBottom: 4 },
  bodyEn: { marginTop: 4, color: '#666' },
});
