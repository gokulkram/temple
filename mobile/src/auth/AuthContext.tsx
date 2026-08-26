import React, { createContext, useCallback, useContext, useEffect, useState, ReactNode } from 'react';
import * as SecureStore from 'expo-secure-store';
import {
  clearConfigCache,
  login as apiLogin,
  logout as apiLogout,
  setToken as setClientToken,
  setUnauthorizedHandler,
} from '../api/client';

const TOKEN_KEY = 'api_token';
const USERNAME_KEY = 'api_username';

interface AuthContextValue {
  token: string | null;
  username: string | null;
  /** Still restoring the token from SecureStore - render a splash, not the login screen, while true. */
  isLoading: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setTokenState] = useState<string | null>(null);
  const [username, setUsername] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // A token can be invalidated from outside this app entirely (a desktop
  // password change, or an explicit logout on another device) - any 401
  // from anywhere in the app funnels here via api/client's unauthorized
  // handler, so every screen's fetch failure bounces to Login uniformly.
  const clearAuth = useCallback(() => {
    setTokenState(null);
    setUsername(null);
    setClientToken(null);
    SecureStore.deleteItemAsync(TOKEN_KEY).catch(() => {});
    SecureStore.deleteItemAsync(USERNAME_KEY).catch(() => {});
    clearConfigCache();
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(clearAuth);
    (async () => {
      try {
        const [storedToken, storedUsername] = await Promise.all([
          SecureStore.getItemAsync(TOKEN_KEY),
          SecureStore.getItemAsync(USERNAME_KEY),
        ]);
        if (storedToken) {
          setClientToken(storedToken);
          setTokenState(storedToken);
          setUsername(storedUsername);
        }
      } finally {
        setIsLoading(false);
      }
    })();
    return () => setUnauthorizedHandler(null);
  }, [clearAuth]);

  const login = useCallback(async (u: string, p: string) => {
    const result = await apiLogin(u, p);
    setClientToken(result.token);
    setTokenState(result.token);
    setUsername(result.username);
    await Promise.all([
      SecureStore.setItemAsync(TOKEN_KEY, result.token),
      SecureStore.setItemAsync(USERNAME_KEY, result.username),
    ]);
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiLogout();
    } catch {
      // Already invalid or unreachable - clear the local session regardless.
    }
    clearAuth();
  }, [clearAuth]);

  return (
    <AuthContext.Provider value={{ token, username, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}
