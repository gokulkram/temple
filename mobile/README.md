# Temple Admin — mobile app

Expo (React Native) companion to `../admin/`, talking to the JSON API under
`../api/` (bearer-token auth, not the web admin's session cookies).

## Layout

```
app/                 expo-router routes (file-based)
  sign-in.tsx         login screen (shown when logged out)
  (app)/              everything behind Stack.Protected(guard: logged in)
    index.tsx         dashboard - tile per section, live counts
    section/[key].tsx  generic editor - one screen drives all 7 sections,
                       driven by the same includes/collections.php config
                       the web admin uses (fetched from GET /api/config.php)
    settings.tsx       site settings
    gallery.tsx        upload / caption / reorder / delete photos
    password.tsx        change password (logs out this and every other device)
src/
  api/client.ts        fetch wrapper + typed endpoint calls
  auth/AuthContext.tsx  token state, SecureStore persistence, 401 -> logout
  config/env.ts         API base URL resolution (see below)
  components/            Field, RowCard, ExistingImagePicker
```

## Running locally

```
npm install
npx expo start
```

Open in Expo Go (scan the QR code) or a dev-client build. The backend
(`../api/`) must already be reachable - see the main README's "Running
locally" section for getting the PHP site + MongoDB Atlas connection working
first.

### API base URL

Set via the `EXPO_PUBLIC_API_BASE_URL` env var (Expo inlines any
`EXPO_PUBLIC_`-prefixed var into the JS bundle automatically):

- **Android emulator** - `http://10.0.2.2/aa/api` (the emulator's alias for
  the host machine's localhost). This is the default if the var is unset.
- **Physical phone on the same Wi-Fi as your dev machine** - the machine's
  LAN IP, e.g. `http://192.168.1.23/aa/api`. Find it with `ipconfig`
  (Windows) and make sure the firewall allows inbound on port 80.
- **iOS Simulator** - `http://localhost/aa/api` works directly.

For `expo start`, put it in a `.env` file (not committed) or export it
before running:
```
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.23/aa/api npx expo start
```

## Building the APK

Every EAS profile forces `android.buildType: "apk"` (EAS defaults to AAB,
which isn't directly sideloadable):

```
npx eas build -p android --profile development   # dev-client APK
npx eas build -p android --profile preview        # for a phone on your LAN
npx eas build -p android --profile production     # points at Render
```

Before a `preview` or `production` build, edit `eas.json`'s
`REPLACE-WITH-...` placeholders:
- `preview` → your machine's LAN IP (same as above)
- `production` → your Render service's HTTPS URL, with `/api` appended

`preview`/`development` builds allow cleartext (plain `http://`) traffic on
Android since local testing has no TLS cert; `production` does not (Android
blocks cleartext by default in a built APK, and the Render URL is always
HTTPS anyway) - this is controlled by `APP_VARIANT` in `app.config.js`.

`--local` works on any profile instead of EAS's cloud build, if preferred.
