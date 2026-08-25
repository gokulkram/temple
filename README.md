# அருள்மிகு ஆனைமலை கருப்பராயர் திருக்கோவில் — website + admin

PHP 8 / MariaDB site with a bilingual (Tamil + English) admin panel.
No framework, no build step: copy the folder to the host and it runs.

## Layout

```
index.php            the public page, rendered entirely from the database
assets/              style.css + main.js (extracted from the original page)
images/              photos + thumbs/  ·  .htaccess blocks execution here
includes/            config, db, auth, sanitize, upload, collections
  .htaccess          denies all direct HTTP access to this folder
admin/               login, dashboard, editors, gallery, password
sql/                 schema.sql + seed.sql (real content, safe to re-run)
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

**Local MySQL is on port 3307, not 3306** — that is this XAMPP's config, and
it is set in `includes/config.php`.

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
- **PDO prepared statements everywhere**, emulation off.
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

1. Copy everything except `_eleventy/`, `_site/` and `_original/`.
2. Create the database, import `sql/schema.sql` then `sql/seed.sql`.
3. Edit `includes/config.php` — host, **port (usually 3306 there)**, name, user,
   password.
4. Confirm `.htaccess` is honoured: `https://yoursite/includes/config.php`
   must return **403**. If it returns the file, the host has `AllowOverride
   None` — move `includes/` above the web root instead.
5. In admin → Site Settings, replace both `REPLACE-WITH-DOMAIN` values with the
   real domain. They must be full `https://` URLs or WhatsApp and Facebook
   previews show no image.
6. Change the admin password. Force HTTPS.

## Deploying to Railway

The repo is set up to build straight from `Dockerfile` — no manual server
config needed.

1. Create a Railway project, add a **MySQL** database plugin, and add this
   repo as a second service (`railway.json` points it at the `Dockerfile`
   build).
2. On the app service's **Variables** tab, add references to the MySQL
   plugin's `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`,
   `MYSQLPASSWORD`. `includes/config.php` reads these directly — once they're
   set there's nothing to edit in code.
3. Import the schema: connect to the MySQL plugin's public connection string
   (Railway shows it on the plugin's **Connect** tab) with any MySQL client
   and run `sql/schema.sql` then `sql/seed.sql`.
4. Deploy. The `Dockerfile` already sets `AllowOverride All` so `.htaccess`
   in `includes/` and `images/` is honoured out of the box, and
   `entrypoint.sh` rebinds Apache to Railway's dynamic `$PORT` at container
   start — no extra config needed for either.
5. If you mount a volume at `images/` (so uploads survive redeploys), leave
   it empty on first attach — `entrypoint.sh` detects the empty volume and
   seeds it from the image's baked-in photos automatically.
6. In admin → Site Settings, replace both `REPLACE-WITH-DOMAIN` values with
   the Railway-assigned (or custom) domain. Change the admin password.

## Backups

The database holds all text; `images/` holds all photos. **Back up both** — a
database restore alone leaves the page pointing at images that no longer exist.

## Still outstanding

The gallery photos came from contributor uploads on Google Maps and are the
copyright of the individual photographers. Get permission, or replace them with
the temple's own photos, before promoting the site.
