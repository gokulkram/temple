module.exports = ({ config }) => ({
  ...config,
  name: 'Temple',
  slug: 'temple-app',
  scheme: 'temple-app',
  version: '1.0.0',
  orientation: 'portrait',
  icon: './assets/icon.png',
  userInterfaceStyle: 'light',
  ios: {
    supportsTablet: true,
  },
  android: {
    package: 'com.stallioni.temple.app',
    adaptiveIcon: {
      backgroundColor: '#f7f2ec',
      foregroundImage: './assets/android-icon-foreground.png',
      backgroundImage: './assets/android-icon-background.png',
      monochromeImage: './assets/android-icon-monochrome.png',
    },
    predictiveBackGestureEnabled: false,
  },
  web: {
    favicon: './assets/favicon.png',
  },
  plugins: ['expo-router', 'expo-status-bar', 'expo-image'],
  extra: {
    eas: {
      projectId: '7f46438b-3bf1-4fa5-8fb4-3c2212f58e3f',
    },
  },
});
