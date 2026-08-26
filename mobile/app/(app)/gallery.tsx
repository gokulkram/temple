import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  Image,
  TextInput,
  Pressable,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { ImageManipulator, SaveFormat } from 'expo-image-manipulator';
import { ApiError, getGallery, saveGallery, uploadGalleryPhotos } from '../../src/api/client';
import { SITE_BASE_URL } from '../../src/config/env';
import type { GalleryPhoto } from '../../src/types';

// Matches includes/config.php's MAX_IMAGE_EDGE - the server never scales an
// image up either (resize_within()), so this only ever shrinks.
const MAX_EDGE = 1600;
const JPEG_QUALITY = 0.85;

interface RowState {
  id: string;
  label: string;
  caption: string;
}

export default function Gallery() {
  const [photos, setPhotos] = useState<GalleryPhoto[]>([]);
  const [rows, setRows] = useState<RowState[]>([]);
  const [deletedIds, setDeletedIds] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const list = await getGallery();
      setPhotos(list);
      setRows(list.map((p) => ({ id: p.id, label: p.label, caption: p.caption })));
      setDeletedIds([]);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not load the gallery.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  function photoFor(id: string): GalleryPhoto | undefined {
    return photos.find((p) => p.id === id);
  }

  function moveRow(index: number, direction: -1 | 1) {
    setRows((prev) => {
      const arr = [...prev];
      const target = index + direction;
      if (target < 0 || target >= arr.length) return prev;
      [arr[index], arr[target]] = [arr[target], arr[index]];
      return arr;
    });
  }

  function updateRow(index: number, field: 'label' | 'caption', value: string) {
    setRows((prev) => {
      const arr = [...prev];
      arr[index] = { ...arr[index], [field]: value };
      return arr;
    });
  }

  function deleteRow(index: number) {
    Alert.alert(
      'Delete this photo?',
      'The file is removed permanently when you save.',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: () => {
            setRows((prev) => {
              const row = prev[index];
              setDeletedIds((d) => [...d, row.id]);
              return prev.filter((_, i) => i !== index);
            });
          },
        },
      ]
    );
  }

  async function pickAndUpload() {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Permission needed', 'Photo library access is required to upload photos.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      quality: 1,
    });
    if (result.canceled || result.assets.length === 0) {
      return;
    }

    setUploading(true);
    setError(null);
    try {
      const files = await Promise.all(
        result.assets.map(async (asset, i) => {
          // Downscale before upload - phone photos routinely exceed the
          // server's 5 MB limit (MAX_UPLOAD_BYTES), and re-encoding here
          // avoids a confusing server-side rejection.
          const targetWidth = Math.min(asset.width || MAX_EDGE, MAX_EDGE);
          const context = ImageManipulator.manipulate(asset.uri);
          context.resize({ width: targetWidth });
          const rendered = await context.renderAsync();
          const saved = await rendered.saveAsync({ format: SaveFormat.JPEG, compress: JPEG_QUALITY });
          return { uri: saved.uri, name: asset.fileName || `photo-${i}.jpg`, type: 'image/jpeg' };
        })
      );

      const uploadResult = await uploadGalleryPhotos(files);
      if (uploadResult.errors.length > 0) {
        setError(uploadResult.errors.join('\n'));
      }
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Upload failed.');
    } finally {
      setUploading(false);
    }
  }

  async function save() {
    setSaving(true);
    setError(null);
    try {
      await saveGallery(
        rows.map((r) => ({ id: r.id, label: r.label, caption: r.caption })),
        deletedIds
      );
      await load();
      Alert.alert('Saved', 'Gallery saved.');
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Save failed partway through - reload to check what was saved.');
    } finally {
      setSaving(false);
    }
  }

  if (loading && photos.length === 0) {
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

        <Pressable style={styles.uploadBtn} onPress={pickAndUpload} disabled={uploading}>
          {uploading ? (
            <ActivityIndicator color="#8B1A1A" />
          ) : (
            <Text style={styles.uploadBtnText}>+ Upload photos</Text>
          )}
        </Pressable>

        <Text style={styles.sectionTitle}>Photos ({rows.length})</Text>
        {rows.length === 0 && <Text style={styles.empty}>No photos yet — upload some above.</Text>}

        {rows.map((row, index) => {
          const photo = photoFor(row.id);
          const imgUri = photo ? `${SITE_BASE_URL}/${photo.has_thumb ? photo.thumb_url : photo.url}` : undefined;
          return (
            <View key={row.id} style={styles.card}>
              <View style={styles.cardRow}>
                {imgUri && <Image source={{ uri: imgUri }} style={styles.thumb} />}
                <View style={styles.cardBody}>
                  <View style={styles.header}>
                    <Text style={styles.index}>{index + 1}</Text>
                    <Pressable disabled={index === 0} onPress={() => moveRow(index, -1)} hitSlop={8}>
                      <Text style={[styles.reorderText, index === 0 && styles.disabled]}>▲</Text>
                    </Pressable>
                    <Pressable disabled={index === rows.length - 1} onPress={() => moveRow(index, 1)} hitSlop={8}>
                      <Text style={[styles.reorderText, index === rows.length - 1 && styles.disabled]}>▼</Text>
                    </Pressable>
                    <View style={styles.spacer} />
                    <Pressable onPress={() => deleteRow(index)} style={styles.deleteBtn}>
                      <Text style={styles.deleteText}>Delete</Text>
                    </Pressable>
                  </View>
                  <Text style={styles.label}>Short label</Text>
                  <TextInput
                    style={styles.input}
                    value={row.label}
                    onChangeText={(v) => updateRow(index, 'label', v)}
                  />
                  <Text style={styles.label}>Caption</Text>
                  <TextInput
                    style={styles.input}
                    value={row.caption}
                    onChangeText={(v) => updateRow(index, 'caption', v)}
                  />
                </View>
              </View>
            </View>
          );
        })}
      </ScrollView>

      <View style={styles.footer}>
        <Pressable style={[styles.saveBtn, saving && styles.saveBtnDisabled]} onPress={save} disabled={saving}>
          {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Save changes</Text>}
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  scroll: { padding: 16, paddingBottom: 100 },
  error: { color: '#8B1A1A', backgroundColor: '#fdecec', padding: 10, borderRadius: 8, marginBottom: 12 },
  uploadBtn: {
    borderWidth: 1,
    borderColor: '#8B1A1A',
    borderRadius: 8,
    paddingVertical: 12,
    alignItems: 'center',
    marginBottom: 20,
  },
  uploadBtnText: { color: '#8B1A1A', fontWeight: '600' },
  sectionTitle: { fontSize: 16, fontWeight: '700', marginBottom: 8 },
  empty: { color: '#999', marginBottom: 10 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#e5e5e5',
    padding: 12,
    marginBottom: 12,
  },
  cardRow: { flexDirection: 'row', gap: 12 },
  thumb: { width: 72, height: 72, borderRadius: 8, backgroundColor: '#eee' },
  cardBody: { flex: 1 },
  header: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 6 },
  index: { fontWeight: '700', color: '#999', width: 18 },
  reorderText: { fontSize: 14, color: '#8B1A1A' },
  disabled: { color: '#ccc' },
  spacer: { flex: 1 },
  deleteBtn: { paddingHorizontal: 8, paddingVertical: 4, backgroundColor: '#fdecec', borderRadius: 6 },
  deleteText: { color: '#8B1A1A', fontSize: 12, fontWeight: '600' },
  label: { fontSize: 12, fontWeight: '600', color: '#333', marginTop: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 6,
    fontSize: 14,
    backgroundColor: '#fff',
  },
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
