import { Image } from 'expo-image';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Dimensions,
  FlatList,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { getPublicContent, ApiError } from '../src/api/client';
import { SITE_BASE_URL } from '../src/config/env';
import GalleryGrid from '../src/components/GalleryGrid';
import type { GalleryPhoto } from '../src/types';

export default function Gallery() {
  const [photos, setPhotos] = useState<GalleryPhoto[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [viewerIndex, setViewerIndex] = useState<number | null>(null);

  useEffect(() => {
    getPublicContent()
      .then((d) => setPhotos(d.gallery_photos))
      .catch((e) => setError(e instanceof ApiError ? e.message : 'Could not load the gallery.'));
  }, []);

  const openViewer = useCallback((_: GalleryPhoto, index: number) => setViewerIndex(index), []);

  if (error) {
    return (
      <View style={styles.center}>
        <Text style={styles.errorText}>{error}</Text>
      </View>
    );
  }

  if (!photos) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#8B1A1A" size="large" />
      </View>
    );
  }

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.gridWrap}>
        <GalleryGrid photos={photos} onPressPhoto={openViewer} />
      </ScrollView>
      <Modal visible={viewerIndex !== null} transparent animationType="fade" onRequestClose={() => setViewerIndex(null)}>
        <PhotoViewer photos={photos} initialIndex={viewerIndex ?? 0} onClose={() => setViewerIndex(null)} />
      </Modal>
    </View>
  );
}

function PhotoViewer({
  photos,
  initialIndex,
  onClose,
}: {
  photos: GalleryPhoto[];
  initialIndex: number;
  onClose: () => void;
}) {
  const width = Dimensions.get('window').width;
  const [index, setIndex] = useState(initialIndex);
  const photo = photos[index];

  return (
    <View style={styles.viewer}>
      <Pressable style={styles.closeButton} onPress={onClose} hitSlop={16}>
        <Text style={styles.closeText}>✕</Text>
      </Pressable>
      <FlatList
        data={photos}
        keyExtractor={(p) => p.id}
        horizontal
        pagingEnabled
        initialScrollIndex={initialIndex}
        getItemLayout={(_, i) => ({ length: width, offset: width * i, index: i })}
        onMomentumScrollEnd={(e) => setIndex(Math.round(e.nativeEvent.contentOffset.x / width))}
        renderItem={({ item }) => (
          <View style={{ width, alignItems: 'center', justifyContent: 'center' }}>
            <Image
              source={{ uri: `${SITE_BASE_URL}/${item.url}` }}
              style={styles.fullImage}
              contentFit="contain"
            />
          </View>
        )}
      />
      {(photo?.label || photo?.caption) ? (
        <View style={styles.captionWrap}>
          {photo.label ? <Text style={styles.captionLabel}>{photo.label}</Text> : null}
          {photo.caption ? <Text style={styles.caption}>{photo.caption}</Text> : null}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#fff' },
  gridWrap: { padding: 8 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 20 },
  errorText: { color: '#8B1A1A', fontSize: 15, textAlign: 'center' },
  viewer: { flex: 1, backgroundColor: 'rgba(0,0,0,0.95)' },
  closeButton: {
    position: 'absolute',
    top: 48,
    right: 20,
    zIndex: 1,
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  closeText: { color: '#fff', fontSize: 18 },
  fullImage: { width: '100%', height: '100%' },
  captionWrap: { position: 'absolute', bottom: 40, left: 20, right: 20 },
  captionLabel: { color: '#fff', fontWeight: '700', textAlign: 'center' },
  caption: { color: '#ddd', fontSize: 13, textAlign: 'center', marginTop: 2 },
});
