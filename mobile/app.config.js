// Dynamic config (not app.json) so the Android cleartext-HTTP allowance can
// be scoped to dev/preview builds only - production keeps it off, since the
// production API base URL (see src/config/env.ts) is always HTTPS.
const APP_VARIANT = process.env.APP_VARIANT || 'development';
const ALLOW_CLEARTEXT = APP_VARIANT !== 'production';

module.exports = ({ config }) => ({
  ...config,
  name: 'Temple Admin',
  slug: 'temple-admin',
  scheme: 'temple-admin',
  version: '1.0.0',
  orientation: 'portrait',
  icon: './assets/icon.png',
  userInterfaceStyle: 'light',
  ios: {
    supportsTablet: true,
  },
  android: {
    package: 'com.stallioni.temple.admin',
    adaptiveIcon: {
      backgroundColor: '#E6F4FE',
      foregroundImage: './assets/android-icon-foreground.png',
      backgroundImage: './assets/android-icon-background.png',
      monochromeImage: './assets/android-icon-monochrome.png',
    },
    predictiveBackGestureEnabled: false,
  },
  web: {
    favicon: './assets/favicon.png',
  },
  extra: {
    eas: {
      projectId: '75a00573-af3a-4cf4-ac37-4a6e1b3e65ac',
    },
  },
  plugins: [
    'expo-router',
    'expo-status-bar',
    'expo-secure-store',
    [
      'expo-build-properties',
      {
        android: {
          usesCleartextTraffic: ALLOW_CLEARTEXT,
        },
      },
    ],
  ],
});
