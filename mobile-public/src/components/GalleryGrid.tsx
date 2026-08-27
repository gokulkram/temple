import React from 'react';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, View } from 'react-native';
import { SITE_BASE_URL } from '../config/env';
import type { GalleryPhoto } from '../types';

const COLUMNS = 3;

export default function GalleryGrid({
  photos,
  onPressPhoto,
}: {
  photos: GalleryPhoto[];
  onPressPhoto?: (photo: GalleryPhoto, index: number) => void;
}) {
  return (
    <View style={styles.grid}>
      {photos.map((photo, i) => (
        <Pressable
          key={photo.id}
          style={styles.tile}
          onPress={() => onPressPhoto?.(photo, i)}
        >
          <Image
            source={{ uri: `${SITE_BASE_URL}/${photo.has_thumb ? photo.thumb_url : photo.url}` }}
            style={styles.image}
            contentFit="cover"
            transition={150}
          />
        </Pressable>
      ))}
    </View>
  );
}

const GAP = 4;

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', marginHorizontal: -GAP / 2 },
  tile: {
    width: `${100 / COLUMNS}%`,
    aspectRatio: 1,
    padding: GAP / 2,
  },
  image: { flex: 1, borderRadius: 8, backgroundColor: '#e8ddce' },
});
