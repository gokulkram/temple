import { Platform } from 'react-native';

/**
 * API base URL, in priority order:
 *   1. EXPO_PUBLIC_API_BASE_URL - set per EAS build profile (see eas.json),
 *      or in a local .env file for `expo start` (Expo inlines any
 *      EXPO_PUBLIC_-prefixed var into the JS bundle automatically).
 *   2. A same-machine dev fallback: 10.0.2.2 is the Android emulator's
 *      alias for the host machine's localhost; iOS Simulator can reach the
 *      host directly as localhost. Neither works from a physical device -
 *      a physical phone MUST set EXPO_PUBLIC_API_BASE_URL to the host
 *      machine's LAN IP (e.g. http://192.168.1.23/aa/api).
 */
const DEV_FALLBACK = Platform.OS === 'android' ? 'http://10.0.2.2/aa/api' : 'http://localhost/aa/api';

export const API_BASE_URL = (process.env.EXPO_PUBLIC_API_BASE_URL || DEV_FALLBACK).replace(/\/+$/, '');

/**
 * The site root (no /api suffix) - where uploaded images actually live
 * (IMAGE_URL in includes/config.php is "images", relative to the site
 * root, not the API). api/images.php and api/gallery.php return bare
 * filenames/relative paths; the client resolves them against this.
 */
export const SITE_BASE_URL = API_BASE_URL.replace(/\/api$/, '');

