import { API_BASE_URL } from '../config/env';
import type { PublicContent } from '../types';

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

async function apiFetch<T>(path: string): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });
  } catch {
    throw new ApiError('Could not reach the server. Check your connection.', 0);
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
    throw new ApiError(payload.error ?? 'Request failed.', response.status);
  }

  return payload.data as T;
}

export function getPublicContent(): Promise<PublicContent> {
  return apiFetch<PublicContent>('/public.php');
}
