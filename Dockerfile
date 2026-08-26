# PHP 8 + Apache, built for Render.
#
# Render assigns a dynamic $PORT and terminates TLS in front of the
# container, so Apache is rewired to listen on $PORT at startup
# (see entrypoint.sh) rather than the usual 80/443.
FROM php:8.2-apache

# NOTE: do not apt-get purge the -dev packages afterwards - --auto-remove
# takes their runtime .so libraries (libpng16, libjpeg, libwebp) with them
# since apt considers those dependencies orphaned, which breaks gd at runtime
# ("cannot open shared object file") even though the build itself succeeds.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
        libssl-dev pkg-config git unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd exif \
    && pecl install mongodb && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# .htaccess in includes/ and images/ is load-bearing for this app's security
# model (see README) - it only works if overrides are actually allowed.
#
# mod_php requires the prefork MPM; a2dismod can silently no-op on a module
# it considers still depended-on, so remove the other MPMs' symlinks
# directly rather than trust it - leaving two MPMs loaded stops Apache
# from starting at all.
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
             /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && a2enmod mpm_prefork rewrite \
    && a2query -M

WORKDIR /var/www/html
COPY . /var/www/html/

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Not part of the running app - see README ("safe to delete") and the loose
# dump/archive at the repo root. scripts/ is the MySQL->Mongo migration
# tool, meant to be run manually from a developer machine, not shipped in
# the image. Keeping them out of the image is just size.
RUN rm -rf _eleventy _original aa.zip aa.sql sample.pdf scripts sql mobile

# A Render disk mounted at images/ starts EMPTY, which would hide the
# photos baked into this image. Keep a pristine copy so the entrypoint can
# seed the (possibly empty) disk on first boot.
RUN cp -r images /var/www/html-images-seed

RUN chown -R www-data:www-data /var/www/html

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
