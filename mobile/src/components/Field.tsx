import React from 'react';
import { View, Text, TextInput, Pressable, StyleSheet } from 'react-native';
import type { FieldDef } from '../types';
import { decodeEntities } from '../utils/decodeEntities';

interface Props {
  def: FieldDef;
  value: string;
  onChange: (v: string) => void;
  /** 'image' fields don't type a filename - they open the existing-images picker (see gallery.php for uploads). */
  onPickImage?: () => void;
}

export function Field({ def, value, onChange, onPickImage }: Props) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>
        {def.label}
        {def.required ? ' *' : ''}
      </Text>
      {renderInput(def, value, onChange, onPickImage)}
      {def.hint ? <Text style={styles.hint}>{decodeEntities(def.hint)}</Text> : null}
      {def.type === 'rich' && (
        <Text style={styles.hint}>
          HTML allowed: {'<br> <em> <strong> <a> <span>'}. Other tags are stripped automatically.
        </Text>
      )}
    </View>
  );
}

function renderInput(def: FieldDef, value: string, onChange: (v: string) => void, onPickImage?: () => void) {
  switch (def.type) {
    case 'area':
    case 'rich':
      return (
        <TextInput
          style={[styles.input, styles.multiline]}
          value={value}
          onChangeText={onChange}
          multiline
          textAlignVertical="top"
        />
      );
    case 'emoji':
      return <TextInput style={[styles.input, styles.emoji]} value={value} onChangeText={onChange} maxLength={8} />;
    case 'image':
      return (
        <Pressable style={styles.imagePicker} onPress={onPickImage}>
          <Text style={value ? styles.imageValue : styles.imagePlaceholder}>
            {value || 'Choose an existing image…'}
          </Text>
        </Pressable>
      );
    default:
      return <TextInput style={styles.input} value={value} onChangeText={onChange} />;
  }
}

const styles = StyleSheet.create({
  field: { marginBottom: 14 },
  label: { fontSize: 13, fontWeight: '600', marginBottom: 4, color: '#333' },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 8,
    fontSize: 15,
    backgroundColor: '#fff',
  },
  multiline: { minHeight: 90 },
  emoji: { width: 70, textAlign: 'center', fontSize: 20 },
  imagePicker: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 10,
    backgroundColor: '#fff',
  },
  imageValue: { fontSize: 15 },
  imagePlaceholder: { fontSize: 15, color: '#999' },
  hint: { fontSize: 12, color: '#777', marginTop: 4 },
});
