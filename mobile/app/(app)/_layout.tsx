import React from 'react';
import { Stack } from 'expo-router';

export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: '#8B1A1A' },
        headerTintColor: '#fff',
        headerTitleStyle: { fontWeight: '600' },
      }}
    >
      <Stack.Screen name="index" options={{ title: 'கோயில் நிர்வாகம்' }} />
      <Stack.Screen name="section/[key]" options={{ title: 'Edit Section' }} />
      <Stack.Screen name="settings" options={{ title: 'Site Settings' }} />
      <Stack.Screen name="gallery" options={{ title: 'Gallery' }} />
      <Stack.Screen name="password" options={{ title: 'Change Password' }} />
    </Stack>
  );
}
