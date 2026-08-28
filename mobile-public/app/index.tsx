import { Image } from 'expo-image';
import { Link, useNavigation } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { getPublicContent, ApiError } from '../src/api/client';
import { SITE_BASE_URL } from '../src/config/env';
import RichText from '../src/components/RichText';
import SectionHeader from '../src/components/SectionHeader';
import DeityCard from '../src/components/DeityCard';
import FestivalItem from '../src/components/FestivalItem';
import SpecialDayCard from '../src/components/SpecialDayCard';
import VisitCard from '../src/components/VisitCard';
import LocationItem from '../src/components/LocationItem';
import GalleryGrid from '../src/components/GalleryGrid';
import type { PublicContent } from '../src/types';

const GALLERY_TEASER_COUNT = 6;

export default function Home() {
  const navigation = useNavigation();
  const [data, setData] = useState<PublicContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    setError(null);
    getPublicContent()
      .then((d) => {
        setData(d);
        navigation.setOptions({ title: d.settings.footer_temple_name || 'Temple' });
      })
      .catch((e) => setError(e instanceof ApiError ? e.message : 'Could not load the site.'))
      .finally(() => setLoading(false));
  }, [navigation]);

  useEffect(() => {
    load();
  }, [load]);

  if (loading && !data) {
    return (
      <View style={styles.center}>
        <ActivityIndicator color="#8B1A1A" size="large" />
      </View>
    );
  }

  if (error && !data) {
    return (
      <View style={styles.center}>
        <Text style={styles.errorText}>{error}</Text>
      </View>
    );
  }

  if (!data) return null;
  const { settings, sections, gallery_photos } = data;
  const mapsUrl =
    settings.latitude && settings.longitude
      ? `https://maps.google.com/maps?q=${settings.latitude},${settings.longitude}`
      : null;

  return (
    <ScrollView
      style={styles.screen}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor="#8B1A1A" />}
    >
      {/* Hero */}
      <View style={styles.hero}>
        {settings.hero_image_url ? (
          <Image
            source={{ uri: `${SITE_BASE_URL}/${settings.hero_image_url}` }}
            style={styles.heroImage}
            contentFit="cover"
            accessibilityLabel={settings.hero_image_alt}
          />
        ) : null}
        <View style={styles.heroOverlay}>
          {settings.brand_ta ? <RichText html={settings.brand_ta} style={styles.brand} /> : null}
          {settings.hero_title_ta ? (
            <RichText html={settings.hero_title_ta} style={styles.heroTitleTa} />
          ) : null}
          <Text style={styles.heroTitleEn}>{settings.hero_title_en}</Text>
          <Text style={styles.heroSubtitleTa}>{settings.hero_subtitle_ta}</Text>
          <Text style={styles.heroSubtitleEn}>{settings.hero_subtitle_en}</Text>
        </View>
      </View>

      {error ? <Text style={styles.errorBanner}>{error}</Text> : null}

      {/* About */}
      <View style={styles.section}>
        <SectionHeader heading={sections.about} />
        {data.about_paragraphs.map((p) => (
          <View key={p.id} style={{ marginBottom: 10 }}>
            <RichText html={p.body_ta} />
            {p.body_en ? <RichText html={p.body_en} style={{ color: '#666', marginTop: 4 }} /> : null}
          </View>
        ))}
        {data.about_rows.map((r) => (
          <View key={r.id} style={styles.aboutRow}>
            <Text style={styles.aboutLabel}>{r.label}</Text>
            <RichText html={r.value_ta} />
            {r.value_en ? <RichText html={r.value_en} style={{ color: '#666' }} /> : null}
          </View>
        ))}
      </View>

      {/* History */}
      <View style={styles.section}>
        <SectionHeader heading={sections.history} />
        {data.history_entries.map((h) => (
          <View key={h.id} style={{ marginBottom: 14 }}>
            <Text style={styles.entryTitleTa}>{h.title_ta}</Text>
            <Text style={styles.entryTitleEn}>{h.title_en}</Text>
            <RichText html={h.body_ta} />
            {h.body_en ? <RichText html={h.body_en} style={{ color: '#666', marginTop: 4 }} /> : null}
          </View>
        ))}
      </View>

      {/* Deities */}
      <View style={styles.section}>
        <SectionHeader heading={sections.deities} />
        {data.deity_cards.map((d) => (
          <DeityCard key={d.id} deity={d} />
        ))}
      </View>

      {/* Festivals */}
      <View style={styles.section}>
        <SectionHeader heading={sections.festivals} />
        {data.festival_items.map((f) => (
          <FestivalItem key={f.id} item={f} />
        ))}
      </View>

      {/* Special Day of the Month */}
      <View style={styles.section}>
        <SectionHeader heading={sections.special_days} />
        {data.special_day_items.map((s) => (
          <SpecialDayCard key={s.id} item={s} />
        ))}
      </View>

      {/* Location */}
      <View style={styles.section}>
        <SectionHeader heading={sections.location} />
        {data.location_items.map((l) => (
          <LocationItem key={l.id} item={l} />
        ))}
        {mapsUrl ? (
          <Pressable style={styles.mapsButton} onPress={() => Linking.openURL(mapsUrl)}>
            <Text style={styles.mapsButtonText}>Open in Maps</Text>
          </Pressable>
        ) : null}
      </View>

      {/* Visit info */}
      <View style={styles.section}>
        <SectionHeader heading={sections.visit} />
        {data.visit_cards.map((v) => (
          <VisitCard key={v.id} card={v} />
        ))}
      </View>

      {/* Gallery teaser */}
      <View style={styles.section}>
        <SectionHeader heading={sections.gallery} />
        <GalleryGrid photos={gallery_photos.slice(0, GALLERY_TEASER_COUNT)} />
        {gallery_photos.length > 0 ? (
          <Link href="/gallery" asChild>
            <Pressable style={styles.viewAllButton}>
              <Text style={styles.viewAllText}>View all photos</Text>
            </Pressable>
          </Link>
        ) : null}
      </View>

      {/* Footer */}
      <View style={styles.footer}>
        <Text style={styles.footerName}>{settings.footer_temple_name}</Text>
        <Text style={styles.footerLocation}>{settings.footer_location}</Text>
        {settings.footer_credits ? <RichText html={settings.footer_credits} style={styles.footerText} /> : null}
        {settings.footer_copyright ? (
          <RichText html={settings.footer_copyright} style={styles.footerText} />
        ) : null}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#fff' },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 20 },
  errorText: { color: '#8B1A1A', fontSize: 15, textAlign: 'center' },
  errorBanner: {
    backgroundColor: '#fdecec',
    color: '#8B1A1A',
    padding: 12,
    margin: 16,
    borderRadius: 8,
  },
  hero: { minHeight: 260 },
  heroImage: StyleSheet.absoluteFill,
  heroOverlay: {
    backgroundColor: 'rgba(0,0,0,0.45)',
    padding: 20,
    paddingTop: 40,
    paddingBottom: 32,
    minHeight: 260,
    justifyContent: 'flex-end',
  },
  brand: { color: '#fff', fontSize: 14, marginBottom: 4 },
  heroTitleTa: { color: '#fff', fontSize: 26, fontWeight: '700' },
  heroTitleEn: { color: '#eee', fontSize: 16, marginTop: 2 },
  heroSubtitleTa: { color: '#fff', fontSize: 14, marginTop: 10 },
  heroSubtitleEn: { color: '#ddd', fontSize: 13, marginTop: 2 },
  section: { padding: 20, borderBottomWidth: 1, borderBottomColor: '#eee' },
  aboutRow: { marginBottom: 10 },
  aboutLabel: { fontSize: 13, color: '#777', marginBottom: 2 },
  entryTitleTa: { fontSize: 17, fontWeight: '700', color: '#333' },
  entryTitleEn: { fontSize: 13, color: '#777', marginBottom: 4 },
  mapsButton: {
    backgroundColor: '#8B1A1A',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  mapsButtonText: { color: '#fff', fontWeight: '700' },
  viewAllButton: {
    borderWidth: 1,
    borderColor: '#8B1A1A',
    borderRadius: 10,
    paddingVertical: 10,
    alignItems: 'center',
    marginTop: 12,
  },
  viewAllText: { color: '#8B1A1A', fontWeight: '700' },
  footer: { padding: 24, alignItems: 'center', backgroundColor: '#f7f2ec' },
  footerName: { fontWeight: '700', color: '#333', textAlign: 'center' },
  footerLocation: { color: '#666', fontSize: 13, marginTop: 2, textAlign: 'center' },
  footerText: { fontSize: 12, textAlign: 'center', marginTop: 6, color: '#999' },
});
