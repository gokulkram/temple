import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  TextInput,
  Pressable,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { getConfig, getRows, saveRows, SaveRowsBody } from '../../../src/api/client';
import { ApiError } from '../../../src/api/client';
import { RowCard } from '../../../src/components/RowCard';
import { ExistingImagePicker } from '../../../src/components/ExistingImagePicker';
import type { CollectionDef } from '../../../src/types';

interface RowState {
  id: string;
  values: Record<string, string>;
}

interface ImageTarget {
  table: string;
  index: number;
  fieldName: string;
}

export default function CollectionEditor() {
  const { key } = useLocalSearchParams<{ key: string }>();
  const router = useRouter();

  const [col, setCol] = useState<CollectionDef | null>(null);
  const [headingTa, setHeadingTa] = useState('');
  const [headingEn, setHeadingEn] = useState('');
  const [lists, setLists] = useState<Record<string, RowState[]>>({});
  const [deleted, setDeleted] = useState<Record<string, string[]>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [imageTarget, setImageTarget] = useState<ImageTarget | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [config, rowsResp] = await Promise.all([getConfig(), getRows(key)]);
      const definition = config.collections[key];
      if (!definition) {
        setError('Unknown section.');
        return;
      }
      setCol(definition);
      setHeadingTa(rowsResp.section?.title_ta ?? '');
      setHeadingEn(rowsResp.section?.title_en ?? '');

      const nextLists: Record<string, RowState[]> = {};
      for (const table of Object.keys(definition.lists)) {
        const fieldNames = definition.lists[table].fields.map((f) => f.name);
        nextLists[table] = (rowsResp.lists[table] ?? []).map((row) => ({
          id: row.id,
          values: Object.fromEntries(fieldNames.map((n) => [n, row[n] ?? ''])),
        }));
      }
      setLists(nextLists);
      setDeleted({});
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not load this section.');
    } finally {
      setLoading(false);
    }
  }, [key]);

  useEffect(() => {
    load();
  }, [load]);

  function addRow(table: string) {
    if (!col) return;
    const fieldNames = col.lists[table].fields.map((f) => f.name);
    setLists((prev) => ({
      ...prev,
      [table]: [...prev[table], { id: '', values: Object.fromEntries(fieldNames.map((n) => [n, ''])) }],
    }));
  }

  function deleteRow(table: string, index: number) {
    Alert.alert('Delete this item?', undefined, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () => {
          // Read the row outside the updater - setState updaters must be
          // pure, and this one triggers a second setState (setDeleted) as
          // a side effect, which must run exactly once regardless of how
          // React schedules the updater.
          const row = lists[table][index];
          setLists((prev) => {
            // The row must be removed from `lists` here, not just marked -
            // if it stayed in `lists` with id:'' it would be re-inserted as
            // a new row in the same save that deletes it by id.
            const nextTable = prev[table].filter((_, i) => i !== index);
            if (row.id !== '') {
              setDeleted((d) => ({ ...d, [table]: [...(d[table] ?? []), row.id] }));
            }
            return { ...prev, [table]: nextTable };
          });
        },
      },
    ]);
  }

  function moveRow(table: string, index: number, direction: -1 | 1) {
    setLists((prev) => {
      const arr = [...prev[table]];
      const target = index + direction;
      if (target < 0 || target >= arr.length) return prev;
      [arr[index], arr[target]] = [arr[target], arr[index]];
      return { ...prev, [table]: arr };
    });
  }

  function updateField(table: string, index: number, fieldName: string, value: string) {
    setLists((prev) => {
      const arr = [...prev[table]];
      arr[index] = { ...arr[index], values: { ...arr[index].values, [fieldName]: value } };
      return { ...prev, [table]: arr };
    });
  }

  async function save() {
    if (!col) return;
    setSaving(true);
    setError(null);
    try {
      const body: SaveRowsBody = {
        lists: Object.keys(col.lists),
        rows: Object.fromEntries(
          Object.entries(lists).map(([table, rows]) => [table, rows.map((r) => ({ id: r.id, ...r.values }))])
        ),
        deleted,
      };
      if (col.section) {
        body.section_title_ta = headingTa;
        body.section_title_en = headingEn;
      }
      await saveRows(key, body);
      await load(); // re-fetch so newly-inserted rows get their real ids
      Alert.alert('Saved', 'Changes saved.');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not save - nothing was changed.');
    } finally {
      setSaving(false);
    }
  }

  if (loading && !col) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
      </View>
    );
  }

  if (error && !col) {
    return (
      <View style={styles.center}>
        <Text style={styles.error}>{error}</Text>
        <Pressable onPress={() => router.back()}>
          <Text style={styles.backLink}>Go back</Text>
        </Pressable>
      </View>
    );
  }

  if (!col) return null;

  return (
    <View style={styles.flex}>
      <Stack.Screen options={{ title: col.label }} />
      <ScrollView contentContainerStyle={styles.scroll}>
        {error && <Text style={styles.error}>{error}</Text>}

        {col.section && (
          <View style={styles.headingSection}>
            <Text style={styles.sectionTitle}>Section heading</Text>
            <View style={styles.headingCard}>
              <Text style={styles.headingLabel}>Title (Tamil)</Text>
              <TextInput style={styles.headingInput} value={headingTa} onChangeText={setHeadingTa} />
              <Text style={styles.headingLabel}>Title (English)</Text>
              <TextInput style={styles.headingInput} value={headingEn} onChangeText={setHeadingEn} />
            </View>
          </View>
        )}

        {Object.entries(col.lists).map(([table, def]) => (
          <View key={table} style={styles.listSection}>
            <Text style={styles.sectionTitle}>
              {def.label} ({lists[table]?.length ?? 0})
            </Text>
            {(lists[table] ?? []).length === 0 && <Text style={styles.empty}>No items yet — use Add below.</Text>}
            {(lists[table] ?? []).map((row, index) => (
              <RowCard
                key={index}
                index={index}
                total={lists[table].length}
                fields={def.fields}
                values={row.values}
                onFieldChange={(name, value) => updateField(table, index, name, value)}
                onDelete={() => deleteRow(table, index)}
                onMoveUp={() => moveRow(table, index, -1)}
                onMoveDown={() => moveRow(table, index, 1)}
                onPickImage={(fieldName) => setImageTarget({ table, index, fieldName })}
              />
            ))}
            <Pressable style={styles.addBtn} onPress={() => addRow(table)}>
              <Text style={styles.addBtnText}>+ Add {def.label}</Text>
            </Pressable>
          </View>
        ))}
      </ScrollView>

      <View style={styles.footer}>
        <Pressable style={[styles.saveBtn, saving && styles.saveBtnDisabled]} onPress={save} disabled={saving}>
          {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Save changes</Text>}
        </Pressable>
      </View>

      <ExistingImagePicker
        visible={imageTarget !== null}
        onClose={() => setImageTarget(null)}
        onSelect={(filename) => {
          if (imageTarget) {
            updateField(imageTarget.table, imageTarget.index, imageTarget.fieldName, filename);
          }
          setImageTarget(null);
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 },
  scroll: { padding: 16, paddingBottom: 100 },
  error: { color: '#8B1A1A', backgroundColor: '#fdecec', padding: 10, borderRadius: 8, marginBottom: 12 },
  backLink: { color: '#8B1A1A', marginTop: 12, fontWeight: '600' },
  sectionTitle: { fontSize: 16, fontWeight: '700', marginBottom: 8, marginTop: 4 },
  headingSection: { marginBottom: 20 },
  headingCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e5e5e5',
    padding: 14,
  },
  headingLabel: { fontSize: 13, fontWeight: '600', marginBottom: 4, marginTop: 8, color: '#333' },
  headingInput: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 8,
    fontSize: 15,
    backgroundColor: '#fff',
  },
  listSection: { marginBottom: 20 },
  empty: { color: '#999', marginBottom: 10 },
  addBtn: {
    borderWidth: 1,
    borderColor: '#8B1A1A',
    borderRadius: 8,
    paddingVertical: 10,
    alignItems: 'center',
  },
  addBtnText: { color: '#8B1A1A', fontWeight: '600' },
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
