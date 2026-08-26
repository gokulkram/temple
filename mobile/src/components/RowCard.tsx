import React from 'react';
import { View, Text, Pressable, StyleSheet } from 'react-native';
import { Field } from './Field';
import type { FieldDef } from '../types';

interface Props {
  index: number;
  total: number;
  fields: FieldDef[];
  values: Record<string, string>;
  onFieldChange: (name: string, value: string) => void;
  onDelete: () => void;
  onMoveUp: () => void;
  onMoveDown: () => void;
  onPickImage: (fieldName: string) => void;
}

export function RowCard({
  index,
  total,
  fields,
  values,
  onFieldChange,
  onDelete,
  onMoveUp,
  onMoveDown,
  onPickImage,
}: Props) {
  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <Text style={styles.index}>{index + 1}</Text>
        <View style={styles.reorderGroup}>
          <Pressable disabled={index === 0} onPress={onMoveUp} hitSlop={8} style={styles.reorderBtn}>
            <Text style={[styles.reorderText, index === 0 && styles.disabled]}>▲</Text>
          </Pressable>
          <Pressable disabled={index === total - 1} onPress={onMoveDown} hitSlop={8} style={styles.reorderBtn}>
            <Text style={[styles.reorderText, index === total - 1 && styles.disabled]}>▼</Text>
          </Pressable>
        </View>
        <View style={styles.spacer} />
        <Pressable onPress={onDelete} style={styles.deleteBtn}>
          <Text style={styles.deleteText}>Delete</Text>
        </Pressable>
      </View>
      {fields.map((f) => (
        <Field
          key={f.name}
          def={f}
          value={values[f.name] ?? ''}
          onChange={(v) => onFieldChange(f.name, v)}
          onPickImage={f.type === 'image' ? () => onPickImage(f.name) : undefined}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e5e5e5',
    padding: 14,
    marginBottom: 12,
  },
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: 10, gap: 8 },
  index: { fontWeight: '700', color: '#999', width: 20 },
  reorderGroup: { flexDirection: 'row', gap: 4 },
  reorderBtn: { padding: 6 },
  reorderText: { fontSize: 14, color: '#8B1A1A' },
  disabled: { color: '#ccc' },
  spacer: { flex: 1 },
  deleteBtn: { paddingHorizontal: 10, paddingVertical: 6, backgroundColor: '#fdecec', borderRadius: 6 },
  deleteText: { color: '#8B1A1A', fontSize: 13, fontWeight: '600' },
});
