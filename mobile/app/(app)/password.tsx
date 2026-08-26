import React, { useState } from 'react';
import { View, Text, TextInput, Pressable, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { ApiError, changePassword } from '../../src/api/client';
import { useAuth } from '../../src/auth/AuthContext';

export default function PasswordScreen() {
  const { logout } = useAuth();
  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [again, setAgain] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  async function save() {
    setError(null);
    if (next.length < 10) {
      setError('New password must be at least 10 characters.');
      return;
    }
    if (next !== again) {
      setError('New passwords do not match.');
      return;
    }
    setSaving(true);
    try {
      await changePassword(current, next, again);
      // The server revokes every token for this user on a password change,
      // including the one this request just used - log out locally right
      // away instead of waiting for the next request to 401.
      Alert.alert('Password changed', 'Please log in again with your new password.', [
        { text: 'OK', onPress: () => logout() },
      ]);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Could not change password.');
    } finally {
      setSaving(false);
    }
  }

  return (
    <View style={styles.container}>
      <Text style={styles.sub}>Change your login password.</Text>
      {error && <Text style={styles.error}>{error}</Text>}

      <Text style={styles.label}>Current password</Text>
      <TextInput style={styles.input} value={current} onChangeText={setCurrent} secureTextEntry autoFocus />

      <Text style={styles.label}>New password</Text>
      <TextInput style={styles.input} value={next} onChangeText={setNext} secureTextEntry />
      <Text style={styles.hint}>At least 10 characters.</Text>

      <Text style={styles.label}>Repeat new password</Text>
      <TextInput style={styles.input} value={again} onChangeText={setAgain} secureTextEntry />

      <Pressable style={[styles.button, saving && styles.buttonDisabled]} onPress={save} disabled={saving}>
        {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Change password</Text>}
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20 },
  sub: { color: '#666', marginBottom: 16 },
  label: { fontSize: 14, fontWeight: '600', marginTop: 14, marginBottom: 4 },
  hint: { fontSize: 12, color: '#777', marginTop: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    backgroundColor: '#fff',
  },
  button: {
    backgroundColor: '#8B1A1A',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 28,
  },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  error: {
    color: '#8B1A1A',
    backgroundColor: '#fdecec',
    padding: 10,
    borderRadius: 8,
    marginBottom: 4,
  },
});
