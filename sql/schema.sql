-- ============================================================
--  அருள்மிகு ஆனைமலை கருப்பராயர் திருக்கோவில் — schema
--  MySQL / MariaDB, utf8mb4 throughout (required for Tamil).
--
--  Every list table carries `sort_order` so the admin can
--  reorder rows by dragging rather than deleting and retyping.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- admin login ----------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`        VARCHAR(64)  NOT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,   -- password_hash(), never plaintext
  `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`    DATETIME     NULL,       -- set after repeated failures
  `last_login`      DATETIME     NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- flat site-wide values (SEO, hero, footer, contact) ----------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `skey`   VARCHAR(64) NOT NULL,
  `svalue` TEXT        NOT NULL,
  `notes`  VARCHAR(255) NULL,                -- hint shown in the admin form
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- section headings (கோயில் பற்றி / About the Temple ...) ----------
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `skey`     VARCHAR(32)  NOT NULL,          -- about, history, deities, ...
  `title_ta` VARCHAR(255) NOT NULL,
  `title_en` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- navigation ----------
DROP TABLE IF EXISTS `nav_links`;
CREATE TABLE `nav_links` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`      VARCHAR(128) NOT NULL,
  `href`       VARCHAR(128) NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- about: prose paragraphs + info table ----------
DROP TABLE IF EXISTS `about_paragraphs`;
CREATE TABLE `about_paragraphs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `body_ta`    TEXT NOT NULL,
  `body_en`    TEXT NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `about_rows`;
CREATE TABLE `about_rows` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label`      VARCHAR(255) NOT NULL,
  `value_ta`   TEXT NOT NULL,
  `value_en`   TEXT NULL,                    -- optional: Pincode has no English line
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- history ----------
DROP TABLE IF EXISTS `history_entries`;
CREATE TABLE `history_entries` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title_ta`   VARCHAR(255) NOT NULL,
  `title_en`   VARCHAR(255) NOT NULL,
  `body_ta`    TEXT NOT NULL,
  `body_en`    TEXT NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- deities ----------
DROP TABLE IF EXISTS `deity_cards`;
CREATE TABLE `deity_cards` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon`       VARCHAR(16)  NOT NULL DEFAULT '',   -- a single emoji
  `name_ta`    VARCHAR(255) NOT NULL,
  `name_en`    VARCHAR(255) NOT NULL,
  `desc_ta`    TEXT NOT NULL,
  `desc_en`    TEXT NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- festivals ----------
DROP TABLE IF EXISTS `festival_items`;
CREATE TABLE `festival_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month_ta`   VARCHAR(128) NOT NULL,
  `month_en`   VARCHAR(128) NOT NULL,        -- may contain <br>
  `title_ta`   VARCHAR(255) NOT NULL,
  `title_en`   VARCHAR(255) NOT NULL,
  `body_ta`    TEXT NOT NULL,
  `body_en`    TEXT NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- location ----------
DROP TABLE IF EXISTS `location_items`;
CREATE TABLE `location_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon`       VARCHAR(16)  NOT NULL DEFAULT '',
  `label`      VARCHAR(255) NOT NULL,
  `value_ta`   TEXT NOT NULL,                -- may contain <a> links
  `value_en`   TEXT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- visitor information ----------
DROP TABLE IF EXISTS `visit_cards`;
CREATE TABLE `visit_cards` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `icon`       VARCHAR(16)  NOT NULL DEFAULT '',
  `title_ta`   VARCHAR(255) NOT NULL,
  `title_en`   VARCHAR(255) NOT NULL,
  `body`       TEXT NOT NULL,                -- bilingual, may contain <br> <em> <span>
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- gallery ----------
DROP TABLE IF EXISTS `gallery_photos`;
CREATE TABLE `gallery_photos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename`   VARCHAR(255) NOT NULL,        -- relative to /images/
  `label`      VARCHAR(255) NOT NULL,        -- short overlay caption
  `caption`    VARCHAR(500) NOT NULL,        -- lightbox caption + alt text
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
