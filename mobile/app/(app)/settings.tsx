import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, ScrollView, Pressable, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { ApiError, getSettings, saveSettings } from '../../src/api/client';
import { Field } from '../../src/components/Field';
import { ExistingImagePicker } from '../../src/components/ExistingImagePicker';
import type { FieldType } from '../../src/types';

export default function Settings() {
  const [groups, setGroups] = useState<Record<string, string[]> | null>(null);
  const [types, setTypes] = useState<Record<string, FieldType>>({});
  const [hints, setHints] = useState<Record<string, string>>({});
  const [values, setValues] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pickingKey, setPickingKey] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const resp = await getSettings();
      setGroups(resp.groups);
      const nextValues: Record<string, string> = {};
      const nextTypes: Record<string, FieldType> = {};
      const nextHints: Record<string, string> = {};
      for (const [k, v] of Object.entries(resp.values)) {
        nextValues[k] = v.svalue;
        nextTypes[k] = v.type;
        nextHints[k] = v.notes;
      }
      setValues(nextValues);
      setTypes(nextTypes);
      setHints(nextHints);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not load settings.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function save() {
    setSaving(true);
    setError(null);
    try {
      await saveSettings(values);
      Alert.alert('Saved', 'Settings saved.');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not save settings.');
    } finally {
      setSaving(false);
    }
  }

  if (loading && !groups) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {error && <Text style={styles.error}>{error}</Text>}
        {groups &&
          Object.entries(groups).map(([title, keys]) => (
            <View key={title} style={styles.group}>
              <Text style={styles.groupTitle}>{title}</Text>
              <View style={styles.card}>
                {keys
                  .filter((k) => k in values)
                  .map((k) => (
                    <Field
                      key={k}
                      def={{ name: k, label: k, type: types[k] ?? 'text', hint: hints[k] || undefined }}
                      value={values[k] ?? ''}
                      onChange={(v) => setValues((prev) => ({ ...prev, [k]: v }))}
                      onPickImage={() => setPickingKey(k)}
                    />
                  ))}
              </View>
            </View>
          ))}
      </ScrollView>

      <View style={styles.footer}>
        <Pressable style={[styles.saveBtn, saving && styles.saveBtnDisabled]} onPress={save} disabled={saving}>
          {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Save changes</Text>}
        </Pressable>
      </View>

      <ExistingImagePicker
        visible={pickingKey !== null}
        onClose={() => setPickingKey(null)}
        onSelect={(filename) => {
          if (pickingKey) {
            setValues((prev) => ({ ...prev, [pickingKey]: filename }));
          }
          setPickingKey(null);
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  scroll: { padding: 16, paddingBottom: 100 },
  error: { color: '#8B1A1A', backgroundColor: '#fdecec', padding: 10, borderRadius: 8, marginBottom: 12 },
  group: { marginBottom: 20 },
  groupTitle: { fontSize: 16, fontWeight: '700', marginBottom: 8 },
  card: { backgroundColor: '#fff', borderRadius: 12, borderWidth: 1, borderColor: '#e5e5e5', padding: 14 },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    padding: 16,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  saveBtn: { backgroundColor: '#8B1A1A', borderRadius: 8, paddingVertical: 14, alignItems: 'center' },
  saveBtnDisabled: { opacity: 0.6 },
  saveBtnText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});
