import React, { useCallback, useState } from 'react';
import { View, Text, FlatList, Pressable, StyleSheet, ActivityIndicator, RefreshControl } from 'react-native';
import { useFocusEffect, useRouter } from 'expo-router';
import { getDashboard } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';
import type { DashboardResponse } from '../../src/types';

interface Tile {
  key: string;
  icon: string;
  label: string;
  countLabel: string;
  href: string;
}

export default function Dashboard() {
  const router = useRouter();
  const { username, logout } = useAuth();
  const [data, setData] = useState<DashboardResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const result = await getDashboard();
      setData(result);
    } catch {
      setError('Could not load the dashboard.');
    } finally {
      setLoading(false);
    }
  }, []);

  // Re-fetch every time this screen regains focus (e.g. coming back from an
  // edit), so tile counts stay live - mirrors admin/index.php's "Dashboard —
  // choose a section to edit. Changes appear on the site immediately."
  useFocusEffect(
    useCallback(() => {
      load();
    }, [load])
  );

  if (loading && !data) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
      </View>
    );
  }

  const tiles: Tile[] = [
    { key: 'settings', icon: '⚙️', label: 'Site Settings', countLabel: 'Name, phone, hero, SEO', href: '/settings' },
    ...(data?.sections ?? []).map((s) => ({
      key: s.key,
      icon: s.icon,
      label: s.label,
      countLabel: `${s.count} items`,
      href: `/section/${s.key}`,
    })),
    { key: 'gallery', icon: '🖼️', label: 'Gallery', countLabel: `${data?.gallery_count ?? 0} photos`, href: '/gallery' },
    { key: 'password', icon: '🔑', label: 'Change Password', countLabel: 'Update your login password', href: '/password' },
  ];

  return (
    <FlatList
      contentContainerStyle={styles.list}
      data={tiles}
      keyExtractor={(t) => t.key}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
      ListHeaderComponent={
        <View>
          <View style={styles.who}>
            <Text style={styles.whoText}>Signed in as {username ?? '…'}</Text>
            <Pressable onPress={() => logout()}>
              <Text style={styles.logout}>Log out</Text>
            </Pressable>
          </View>
          {error && <Text style={styles.error}>{error}</Text>}
        </View>
      }
      renderItem={({ item }) => (
        <Pressable style={styles.tile} onPress={() => router.push(item.href as never)}>
          <Text style={styles.icon}>{item.icon}</Text>
          <View style={styles.tileText}>
            <Text style={styles.tileLabel}>{item.label}</Text>
            <Text style={styles.tileCount}>{item.countLabel}</Text>
          </View>
        </Pressable>
      )}
    />
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  list: { padding: 16, gap: 10 },
  who: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  whoText: { color: '#666', fontSize: 13 },
  logout: { color: '#8B1A1A', fontSize: 13, fontWeight: '600' },
  tile: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    padding: 16,
    borderRadius: 12,
    backgroundColor: '#f7f2ec',
    borderWidth: 1,
    borderColor: '#e8ddce',
  },
  icon: { fontSize: 28 },
  tileText: { flex: 1 },
  tileLabel: { fontSize: 16, fontWeight: '600' },
  tileCount: { fontSize: 13, color: '#666', marginTop: 2 },
  error: { color: '#8B1A1A', backgroundColor: '#fdecec', padding: 10, borderRadius: 8, marginBottom: 8 },
});
