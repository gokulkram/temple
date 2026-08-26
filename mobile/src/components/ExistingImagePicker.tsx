import React, { useEffect, useState } from 'react';
import { Modal, View, Text, FlatList, Pressable, Image, StyleSheet, ActivityIndicator } from 'react-native';
import { getImages } from '../api/client';
import { SITE_BASE_URL } from '../config/env';

interface Props {
  visible: boolean;
  onSelect: (filename: string) => void;
  onClose: () => void;
}

/**
 * Picks from every image already on disk (existing_images(), via
 * GET /api/images.php) - matches image_field() in admin/_layout.php exactly:
 * new photos are uploaded on the Gallery screen, never from here.
 */
export function ExistingImagePicker({ visible, onSelect, onClose }: Props) {
  const [images, setImages] = useState<string[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!visible) {
      return;
    }
    setImages(null);
    setError(null);
    getImages()
      .then(setImages)
      .catch(() => setError('Could not load images.'));
  }, [visible]);

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.title}>Choose an image</Text>
          <Pressable onPress={onClose}>
            <Text style={styles.close}>Close</Text>
          </Pressable>
        </View>

        {error && <Text style={styles.error}>{error}</Text>}
        {!images && !error && <ActivityIndicator style={styles.loading} />}

        {images && (
          <FlatList
            data={images}
            keyExtractor={(f) => f}
            numColumns={3}
            contentContainerStyle={styles.grid}
            ListEmptyComponent={<Text style={styles.empty}>No photos yet — upload some on the Gallery screen.</Text>}
            renderItem={({ item }) => (
              <Pressable style={styles.cell} onPress={() => onSelect(item)}>
                <Image source={{ uri: `${SITE_BASE_URL}/images/${item}` }} style={styles.thumb} />
                <Text style={styles.filename} numberOfLines={1}>
                  {item}
                </Text>
              </Pressable>
            )}
          />
        )}
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, paddingTop: 50, backgroundColor: '#fff' },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingBottom: 12,
  },
  title: { fontSize: 18, fontWeight: '700' },
  close: { color: '#8B1A1A', fontSize: 15, fontWeight: '600' },
  loading: { marginTop: 40 },
  error: { color: '#8B1A1A', padding: 16 },
  grid: { padding: 8 },
  cell: { flex: 1 / 3, padding: 6, alignItems: 'center' },
  thumb: { width: '100%', aspectRatio: 1, borderRadius: 8, backgroundColor: '#eee' },
  filename: { fontSize: 11, color: '#666', marginTop: 4, width: '100%', textAlign: 'center' },
  empty: { textAlign: 'center', marginTop: 40, color: '#999', paddingHorizontal: 24 },
});
