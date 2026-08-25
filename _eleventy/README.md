# அருள்மிகு ஆனைமலை கருப்பராயர் திருக்கோவில் — website

Static site built with [Eleventy](https://www.11ty.dev/), edited through
[Sveltia CMS](https://github.com/sveltia/sveltia-cms), hosted on Netlify.

## How it fits together

```
src/_data/*.yml   ← all editable content (the CMS writes here)
src/index.njk     ← the page template (markup, CSS and JS live here)
images/           ← photos (the CMS uploads here)
admin/            ← the CMS itself: index.html + config.yml
        ↓  npm run build
_site/index.html  ← what gets published
```

Editing content never means touching HTML. Changing the *design* means editing
`src/index.njk`.

## Local development

```bash
npm install
npm start          # http://localhost:8080 with live reload
npm run build      # one-off build into _site/
```

## Going live — remaining setup

These three steps are not done yet.

### 1. Push to GitHub

```bash
git remote add origin https://github.com/<you>/<repo>.git
git push -u origin main
```

### 2. Connect Netlify

Netlify → **Add new site** → **Import an existing project** → pick the repo.
Build settings come from `netlify.toml`, so accept the defaults.

⚠️ Netlify moved new accounts onto a credit-based free tier, and **every CMS save
triggers a deploy**. If the quota turns out to be tight, Cloudflare Pages hosts
the same repo unchanged — build command `npm run build`, output `_site`.

### 3. Point the CMS at the repo

Two edits, both required:

- `admin/config.yml` → set `repo:` to `<you>/<repo>`
- `src/_data/site.yml` → replace both `REPLACE-WITH-DOMAIN` values with the real
  domain. They **must** be full `https://` URLs or WhatsApp and Facebook link
  previews will show no image.

### 4. Choose how the editor signs in

Sveltia's GitHub backend supports two options:

| | Setup | Editor experience |
|---|---|---|
| **Token login** | Nothing to deploy | Editor creates a GitHub token and pastes it in |
| **Sign in with GitHub** | Deploy [sveltia-cms-auth](https://github.com/sveltia/sveltia-cms-auth) (free Cloudflare Worker, ~15 min), then add `base_url:` to `admin/config.yml` | Normal login button |

The second is worth the 15 minutes for a non-technical editor.

**Either way the editor needs a GitHub account with write access to the repo.**
That is inherent to a Git-based CMS. If that is a blocker, the alternative is a
PHP admin on shared hosting, which is a different build.

## Editing the site

Go to `https://<your-domain>/admin/`, sign in, edit, press **Publish**.
The site rebuilds in about 30 seconds.

Photos are resized to 1600px WebP in the browser before upload, and anything
over 5 MB is rejected — so a large phone photo cannot bloat the repo.

## Backups

Every change is a Git commit. To roll back, revert the commit on GitHub and the
site rebuilds itself. `_original/index.html` is the hand-written page this was
built from, kept for reference.

## Still outstanding

The gallery photos came from contributor uploads on Google Maps and are
copyright of the individual photographers. Get permission or replace them with
the temple's own photos before promoting the site.
