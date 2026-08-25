# அருள்மிகு ஆனைமலை கருப்பராயர் திருக்கோவில் — website + admin

PHP 8 / MongoDB site with a bilingual (Tamil + English) admin panel.
No framework, no build step: copy the folder to the host and it runs
(`composer install` is needed once, for the MongoDB driver library).

## Layout

```
index.php            the public page, rendered entirely from the database
assets/              style.css + main.js (extracted from the original page)
images/              photos + thumbs/  ·  .htaccess blocks execution here
includes/            config, db, auth, sanitize, upload, collections
  .htaccess          denies all direct HTTP access to this folder
admin/               login, dashboard, editors, gallery, password
sql/                 old MariaDB schema.sql + seed.sql - kept for reference,
                     not used since the move to MongoDB
scripts/             migrate-to-mongo.php - one-off MySQL->Mongo migration,
                     not part of the deployed app
_original/           the hand-written HTML this was built from
_eleventy/           earlier Git-CMS attempt, unused - safe to delete
```

## Running locally

Already linked into XAMPP: **http://localhost/aa/** — the folder is a
directory junction, so edits here are live immediately.

```
Site   http://localhost/aa/index.php
Admin  http://localhost/aa/admin/
```

To remove the link later: `rmdir "E:\xmapp\htdocs\aa"` (removes the junction
only, never the real folder).

There is no local MongoDB — set `MONGODB_URI` (and optionally `MONGODB_DB`,
default `temple`) as an environment variable before running locally, pointed
at a scratch Atlas database (e.g. `temple_dev`), not production.

## Admin

Username `admin`. **Change the password on first login** (Password page).

| Page | What it does |
|---|---|
| Dashboard | tiles with live item counts |
| Site Settings | temple details, hero, SEO/sharing, footer |
| 7 section editors | add / edit / delete / drag-reorder every item |
| Gallery | multi-upload, captions, reorder, delete |
| Password | change your own password |

One generic editor (`admin/edit.php`) drives all seven sections from
`includes/collections.php`. **Adding a field means adding one line there**, not
writing a new page.

## Security design

- **Login** — bcrypt, session ID regenerated on login, `HttpOnly` +
  `SameSite=Strict` cookies, 30-minute idle timeout, 15-minute lockout after
  5 failed attempts.
- **CSRF token on every write.** Verified: no token and wrong token both 400.
- **Parameterized queries everywhere** — the MongoDB driver's query documents
  are never built by string-concatenating user input, and multi-document
  writes in `admin/edit.php` run inside a client-session transaction for the
  same all-or-nothing guarantee the old PDO transactions gave.
- **HTML allowlist** (`includes/sanitize.php`). Page content legitimately
  contains `<br>`, `<a href="tel:">`, `<em>`, `<span class="time">`, so output
  cannot simply be escaped. Anything else — `<script>`, `onerror`,
  `javascript:`, `<iframe>` — is stripped, on save *and* on output. The
  function is idempotent, which matters because it runs twice.
- **Uploads** — four layers: browser-supplied name and MIME ignored, real type
  read from the file's bytes, image decoded and **re-encoded through GD** (PHP
  hidden in a JPEG does not survive being turned back into pixels), and a
  generated filename always ending `.jpg`.
- **`.htaccess`** — `includes/` denied outright (verified 403); `images/`
  refuses to execute scripts three different ways, so it works whether the host
  runs mod_php, CGI or FastCGI (verified: `.php` there returns 403).

### Deletion is explicit — do not "simplify" this

Every save could once delete rows by *absence*: whatever the browser did not
resubmit was removed. A malformed or truncated POST then looked exactly like
"the admin deleted everything", and it emptied tables and unlinked image files.

The browser now names the ids it deleted (`deleted[table][]`), and the server
deletes **only** those. The worst a broken submission can do is fail to save an
edit. Reverting to delete-by-absence brings the data loss straight back.

## Deploying to a real host

1. Copy everything except `_eleventy/`, `_site/`, `_original/` and `scripts/`.
2. Run `composer install --no-dev` (needs the `mongodb` PHP extension
   installed on the host).
3. Create a MongoDB database (Atlas or self-hosted) and set `MONGODB_URI`
   (and `MONGODB_DB` if not using the default `temple`) as environment
   variables. There's no schema to import — collections are created on
   first write — but you do need to seed initial content; see
   `scripts/migrate-to-mongo.php` if migrating from an existing MySQL copy,
   or create the admin `users` document by hand otherwise.
4. Confirm `.htaccess` is honoured: `https://yoursite/includes/config.php`
   must return **403**. If it returns the file, the host has `AllowOverride
   None` — move `includes/` above the web root instead.
5. In admin → Site Settings, replace both `REPLACE-WITH-DOMAIN` values with the
   real domain. They must be full `https://` URLs or WhatsApp and Facebook
   previews show no image.
6. Change the admin password. Force HTTPS.

## Deploying to Render

The repo is set up to build straight from `Dockerfile` — no manual server
config needed. The database itself is **MongoDB Atlas**, not anything
Render provides — Render only hosts the app (a Docker web service:
Apache/PHP + a persistent disk for `images/`). `render.yaml` describes the
service as a Blueprint; using it is optional but saves re-entering these
settings by hand.

1. In the Render dashboard, create a new **Blueprint** (or plain **Web
   Service**) from this repo — Render detects `render.yaml`
   automatically if present, or pick **Docker** as the runtime and point it
   at `Dockerfile` otherwise. Use the **Starter** plan or above — the free
   tier has no persistent disk, so uploaded gallery photos would be wiped on
   every redeploy.
2. Attach a persistent disk mounted at `/var/www/html/images` (already
   declared in `render.yaml`, 1 GB is plenty for this site's photo count).
3. Create a MongoDB Atlas cluster (a free/shared tier works — it's still a
   replica set, so the transactions `admin/edit.php` relies on are
   supported), a database user, and a database named `temple`.
4. On the service's **Environment** tab, set `MONGODB_URI` to the Atlas
   connection string (Atlas → **Connect** → **Drivers** → copy the full
   `mongodb+srv://user:pass@cluster.../` URI) and `MONGODB_DB` to `temple`
   if it isn't already the default. `includes/config.php` reads these
   directly — nothing else to edit in code.
5. The `Dockerfile` builds the `mongodb` PHP extension and runs
   `composer install` automatically — no manual driver setup needed.
6. Seed initial content into Atlas — either run
   `scripts/migrate-to-mongo.php` from a machine that can reach both the old
   MySQL source and Atlas, or create the `settings`/`sections`/`users`
   documents by hand for a fresh site (see `sql/seed.sql` for the shape and
   values, minus the `users` row, which has no seed and must be created
   directly).
7. Deploy. The `Dockerfile` already sets `AllowOverride All` so `.htaccess`
   in `includes/` and `images/` is honoured out of the box, and
   `entrypoint.sh` rebinds Apache to Render's dynamic `$PORT` at container
   start — no extra config needed for either.
8. The disk mounted at `images/` starts empty on first attach —
   `entrypoint.sh` detects that and seeds it from the image's baked-in
   photos automatically.
9. In admin → Site Settings, replace both `REPLACE-WITH-DOMAIN` values with
   the Render-assigned (or custom) domain. Change the admin password.

## Backups

The database holds all text; `images/` holds all photos. **Back up both** — a
database restore alone leaves the page pointing at images that no longer exist.

## Still outstanding

The gallery photos came from contributor uploads on Google Maps and are the
copyright of the individual photographers. Get permission, or replace them with
the temple's own photos, before promoting the site.
