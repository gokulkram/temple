import { API_BASE_URL } from '../config/env';
import type {
  ConfigResponse,
  DashboardResponse,
  GalleryPhoto,
  RowsResponse,
  SettingsResponse,
} from '../types';

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

let currentToken: string | null = null;
let unauthorizedHandler: (() => void) | null = null;

/** Called once by AuthContext on mount/token-change - never call this from a screen. */
export function setToken(token: string | null): void {
  currentToken = token;
}

/** Called once by AuthContext on mount to be notified of a 401 from anywhere in the app. */
export function setUnauthorizedHandler(fn: (() => void) | null): void {
  unauthorizedHandler = fn;
}

interface FetchOptions {
  method?: 'GET' | 'POST';
  jsonBody?: unknown;
  formData?: FormData;
  /** Skip attaching the bearer token - only login.php needs this. */
  skipAuth?: boolean;
}

async function apiFetch<T>(path: string, opts: FetchOptions = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (!opts.skipAuth && currentToken) {
    headers.Authorization = `Bearer ${currentToken}`;
  }

  let body: BodyInit | undefined;
  if (opts.formData) {
    body = opts.formData; // fetch sets the multipart Content-Type (with boundary) itself
  } else if (opts.jsonBody !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(opts.jsonBody);
  }

  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, { method: opts.method ?? 'GET', headers, body });
  } catch {
    throw new ApiError('Could not reach the server. Check your connection and the API address.', 0);
  }

  // Defensive: db()'s 503 (Mongo connection failure) and a raw PHP fatal
  // error page are both plain text/HTML, not JSON - never let JSON.parse
  // crash the app over those.
  let payload: { ok: boolean; data?: T; error?: string } | null = null;
  try {
    payload = await response.json();
  } catch {
    payload = null;
  }

  if (!payload) {
    throw new ApiError('The server returned an unexpected response.', response.status);
  }

  if (!payload.ok) {
    if (response.status === 401) {
      unauthorizedHandler?.();
    }
    throw new ApiError(payload.error ?? 'Request failed.', response.status);
  }

  return payload.data as T;
}

export function login(username: string, password: string) {
  return apiFetch<{ token: string; username: string; expires_at: string }>('/login.php', {
    method: 'POST',
    jsonBody: { username, password },
    skipAuth: true,
  });
}

export function logout() {
  return apiFetch<void>('/logout.php', { method: 'POST' });
}

export function getDashboard() {
  return apiFetch<DashboardResponse>('/dashboard.php');
}

// includes/collections.php rarely changes within a session, and every
// section screen needs it - cache it once instead of refetching per screen.
let configCache: ConfigResponse | null = null;

export function getConfig(): Promise<ConfigResponse> {
  if (configCache) {
    return Promise.resolve(configCache);
  }
  return apiFetch<ConfigResponse>('/config.php').then((c) => {
    configCache = c;
    return c;
  });
}

/** Called on logout so a later login (possibly a different deployment) doesn't see a stale config. */
export function clearConfigCache(): void {
  configCache = null;
}

export function getRows(key: string) {
  return apiFetch<RowsResponse>(`/rows.php?c=${encodeURIComponent(key)}`);
}

export interface SaveRowsBody {
  section_title_ta?: string;
  section_title_en?: string;
  lists: string[];
  rows: Record<string, Array<Record<string, string>>>;
  deleted: Record<string, string[]>;
}

export function saveRows(key: string, body: SaveRowsBody) {
  return apiFetch<void>(`/rows.php?c=${encodeURIComponent(key)}`, { method: 'POST', jsonBody: body });
}

export function getSettings() {
  return apiFetch<SettingsResponse>('/settings.php');
}

export function saveSettings(values: Record<string, string>) {
  return apiFetch<void>('/settings.php', { method: 'POST', jsonBody: { s: values } });
}

export function getGallery() {
  return apiFetch<GalleryPhoto[]>('/gallery.php');
}

export function saveGallery(rows: Array<{ id: string; label: string; caption: string }>, deleted: string[]) {
  return apiFetch<void>('/gallery.php', { method: 'POST', jsonBody: { rows, deleted } });
}

export function uploadGalleryPhotos(files: Array<{ uri: string; name: string; type: string }>) {
  const form = new FormData();
  files.forEach((f, i) => {
    // React Native's fetch/FormData accepts {uri,name,type} objects in place
    // of a Blob - this is the standard RN idiom, not a web File API.
    form.append(`photos[${i}]`, { uri: f.uri, name: f.name, type: f.type } as unknown as Blob);
  });
  return apiFetch<{ added: number; errors: string[] }>('/gallery_upload.php', { method: 'POST', formData: form });
}

export function getImages() {
  return apiFetch<string[]>('/images.php');
}

export function changePassword(current: string, newPassword: string, again: string) {
  return apiFetch<void>('/password.php', {
    method: 'POST',
    jsonBody: { current, new: newPassword, again },
  });
}
