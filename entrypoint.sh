#!/bin/sh
# Container entrypoint (Render): bind Apache to $PORT, seed images/ if a
# freshly-mounted (empty) disk has replaced the baked-in photos, then run.
set -e

PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# A fresh disk mount can ship its own root-owned lost+found/ dir - that
# alone makes the mount point non-empty, so an "is it empty" check never
# fires. Use an explicit marker instead of inferring emptiness.
if [ ! -f /var/www/html/images/.seeded ]; then
    echo "images/ not yet seeded - copying in the image's baked-in photos"
    cp -r /var/www/html-images-seed/. /var/www/html/images/
    touch /var/www/html/images/.seeded
    chown -R www-data:www-data /var/www/html/images
fi

# Belt-and-suspenders: whatever the image ended up with, force prefork-only
# right before Apache actually starts (this runs every container start, so
# it can't be undermined by build-layer ordering).
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

exec apache2-foreground
