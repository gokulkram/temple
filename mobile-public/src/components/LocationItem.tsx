import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import RichText from './RichText';
import type { LocationItem as LocationItemType } from '../types';

export default function LocationItem({ item }: { item: LocationItemType }) {
  return (
    <View style={styles.row}>
      <Text style={styles.icon}>{item.icon}</Text>
      <View style={styles.text}>
        <Text style={styles.label}>{item.label}</Text>
        <RichText html={item.value_ta} />
        {item.value_en ? <RichText html={item.value_en} style={styles.valueEn} /> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', marginBottom: 14, gap: 12 },
  icon: { fontSize: 20, width: 28, textAlign: 'center' },
  text: { flex: 1 },
  label: { fontSize: 13, color: '#777', marginBottom: 2 },
  valueEn: { marginTop: 2, color: '#666' },
});
