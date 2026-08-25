<?php
/**
 * Public home page. All content is read from the database; nothing on this
 * page is hard-coded except the layout itself.
 *
 * Plain values go through esc(); the few fields that legitimately contain
 * markup go through clean_html(), which enforces a tag allowlist.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/sanitize.php';

$set           = settings();
$sec           = sections();
$nav           = all('nav_links');
$aboutParas    = all('about_paragraphs');
$aboutRows     = all('about_rows');
$history       = all('history_entries');
$deities       = all('deity_cards');
$festivals     = all('festival_items');
$locationItems = all('location_items');
$visitCards    = all('visit_cards');
$photos        = all('gallery_photos');

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= esc($set['meta_description']) ?>">
    <meta name="theme-color" content="#8B1A1A">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= esc($set['og_site_name']) ?>">
    <meta property="og:title" content="<?= esc($set['og_title']) ?>">
    <meta property="og:description" content="<?= esc($set['og_description']) ?>">
    <!-- TODO: replace REPLACE-WITH-DOMAIN below with the real domain once deployed.
         og:url and og:image MUST be absolute URLs or link previews (WhatsApp, Facebook,
         Twitter) will show no image. -->
    <meta property="og:url" content="<?= esc($set['og_url']) ?>">
    <meta property="og:image" content="<?= esc($set['og_image']) ?>">
    <meta property="og:locale" content="ta_IN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= esc($set['og_title']) ?>">
    <meta name="twitter:description" content="<?= esc($set['twitter_description']) ?>">
    <meta name="twitter:image" content="<?= esc($set['og_image']) ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='18' fill='%238B1A1A'/%3E%3Ctext x='50' y='72' font-size='64' text-anchor='middle' fill='%23D4A017'%3E%E0%AE%93%3C/text%3E%3C/svg%3E">
    <title><?= esc($set['title_tag']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@300;400;500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . "/assets/style.css") ?>">
</head>
<body>

    <!-- NAVIGATION -->
    <nav class="nav" id="nav">
        <div class="nav-inner">
            <a href="#hero" class="nav-brand">
                <div class="nav-om">ॐ</div>
                <div class="nav-title"><?= clean_html($set['brand_ta']) ?></div>
            </a>
            <button class="nav-mobile-toggle" id="navToggle" aria-label="Toggle menu">☰</button>
            <ul class="nav-links" id="navLinks">
<?php foreach ($nav as $l): ?>
                <li><a href="<?= esc($l['href']) ?>"><?= esc($l['label']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="hero">
        <div class="hero-bg"></div>
        <div class="hero-gopuram"></div>
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="hero-content">
            <img
                src="<?= esc(IMAGE_URL . "/" . $set['hero_image']) ?>"
                width="600" height="600"
                alt="<?= esc($set['hero_image_alt']) ?>"
                class="hero-god-image"
            >
            <div class="hero-lamp-row">
                <span class="lamp-icon">🪔</span>
                <span class="lamp-icon">🪔</span>
                <span class="lamp-icon">🪔</span>
            </div>
            <h1 class="hero-title-tamil"><?= clean_html($set['hero_title_ta']) ?></h1>
            <div class="hero-title-english"><?= esc($set['hero_title_en']) ?></div>
            <div class="hero-decorative-line"></div>
            <div class="hero-subtitle"><?= esc($set['hero_subtitle_ta']) ?></div>
            <div class="hero-location"><?= esc($set['hero_subtitle_en']) ?></div>
        </div>
        <div class="hero-scroll-indicator">
            <span><?= esc($set['hero_scroll_hint']) ?></span>
            <div class="scroll-arrow"></div>
        </div>
    </section>

    <!-- TEMPLE BORDER -->
    <div class="temple-border-top"></div>

    <!-- ABOUT -->
    <section class="section about" id="about">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['about']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['about']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line"></div>
                <div class="dot"></div>
                <div class="line"></div>
            </div>
            </div>
            <div class="about-grid">
                <div class="about-text reveal">
<?php foreach ($aboutParas as $p): ?>
                    <p><?= clean_html($p['body_ta']) ?></p>
                    <p class="english-text"><?= clean_html($p['body_en']) ?></p>
<?php endforeach; ?>
                </div>
                <div class="about-info-card reveal">
<?php foreach ($aboutRows as $r): ?>
                    <div class="info-item">
                        <div class="info-label"><?= esc($r['label']) ?></div>
                        <div class="info-value"><?= clean_html($r['value_ta']) ?></div>
<?php if (!empty($r['value_en'])): ?>
                        <div class="info-value-en"><?= clean_html($r['value_en']) ?></div>
<?php endif; ?>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- HISTORY -->
    <section class="section history" id="history">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['history']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['history']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line"></div>
                <div class="dot"></div>
                <div class="line"></div>
            </div>
            </div>
            <div class="history-content reveal">
                <div class="history-timeline-line"></div>
                <div class="history-entries">
<?php foreach ($history as $e): ?>
                    <div class="history-entry">
                        <div class="history-entry-title"><?= esc($e['title_ta']) ?></div>
                        <div class="history-entry-title-en"><?= esc($e['title_en']) ?></div>
                        <p><?= clean_html($e['body_ta']) ?></p>
                        <p class="en"><?= clean_html($e['body_en']) ?></p>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- DEITIES -->
    <section class="section deities" id="deities">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['deities']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['deities']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line" style="background: linear-gradient(90deg, transparent, var(--gold-deep));"></div>
                <div class="dot"></div>
                <div class="line" style="background: linear-gradient(90deg, var(--gold-deep), transparent);"></div>
            </div>
            </div>
            <div class="deities-grid">
<?php foreach ($deities as $c): ?>
                <div class="deity-card reveal">
                    <div class="deity-icon"><?= esc($c['icon']) ?></div>
                    <div class="deity-name-tamil"><?= esc($c['name_ta']) ?></div>
                    <div class="deity-name-english"><?= esc($c['name_en']) ?></div>
                    <div class="deity-desc">
                        <?= clean_html($c['desc_ta']) ?>
                        <span class="en"><?= clean_html($c['desc_en']) ?></span>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FESTIVALS -->
    <section class="section festivals" id="festivals">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['festivals']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['festivals']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line"></div>
                <div class="dot"></div>
                <div class="line"></div>
            </div>
            </div>
            <div class="festivals-list">
<?php foreach ($festivals as $f): ?>
                <div class="festival-item reveal">
                    <div class="festival-month">
                        <div class="festival-month-tamil"><?= esc($f['month_ta']) ?></div>
                        <div class="festival-month-en"><?= clean_html($f['month_en']) ?></div>
                    </div>
                    <div class="festival-details">
                        <h3><?= esc($f['title_ta']) ?></h3>
                        <h4><?= esc($f['title_en']) ?></h4>
                        <p><?= clean_html($f['body_ta']) ?></p>
                        <p class="en"><?= clean_html($f['body_en']) ?></p>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- LOCATION -->
    <section class="section location" id="location">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['location']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['location']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line"></div>
                <div class="dot"></div>
                <div class="line"></div>
            </div>
            </div>
            <div class="location-grid">
                <div class="map-container reveal">
                    <iframe
                        src="https://maps.google.com/maps?q=<?= esc($set['latitude']) ?>,<?= esc($set['longitude']) ?>&amp;hl=ta&amp;z=17&amp;output=embed"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Anaimalai Karupparayan Temple Location">
                    </iframe>
                </div>
                <div class="location-info reveal">
<?php foreach ($locationItems as $i): ?>
                    <div class="location-info-item">
                        <div class="location-icon"><?= esc($i['icon']) ?></div>
                        <div>
                            <div class="location-detail-label"><?= esc($i['label']) ?></div>
                            <div class="location-detail-value"><?= clean_html($i['value_ta']) ?></div>
<?php if (!empty($i['value_en'])): ?>
                            <div class="location-detail-value-en"><?= clean_html($i['value_en']) ?></div>
<?php endif; ?>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- VISIT INFO -->
    <section class="section visit" id="visit">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['visit']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['visit']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line" style="background: linear-gradient(90deg, transparent, var(--gold-deep));"></div>
                <div class="dot"></div>
                <div class="line" style="background: linear-gradient(90deg, var(--gold-deep), transparent);"></div>
            </div>
            </div>
            <div class="visit-grid">
<?php foreach ($visitCards as $c): ?>
                <div class="visit-card reveal">
                    <div class="visit-card-icon"><?= esc($c['icon']) ?></div>
                    <div class="visit-card-title"><?= esc($c['title_ta']) ?></div>
                    <div class="visit-card-title-en"><?= esc($c['title_en']) ?></div>
                    <div class="visit-card-content"><?= clean_html($c['body']) ?></div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- GALLERY -->
    <section class="section gallery" id="gallery">
        <div class="section-inner">
            <div class="section-header reveal">
                <div class="section-title-tamil"><?= esc($sec['gallery']['title_ta']) ?></div>
                <div class="section-title-english"><?= esc($sec['gallery']['title_en']) ?></div>
            <div class="section-ornament">
                <div class="line"></div>
                <div class="dot"></div>
                <div class="line"></div>
            </div>
            </div>
            <div class="gallery-track-wrapper">
                <div class="gallery-track" id="galleryTrack">
<?php foreach ($photos as $idx => $p): ?>
                    <div class="gallery-slide" onclick="openLightbox(<?= (int) $idx ?>)">
                        <img src="<?= esc(IMAGE_URL . "/" . $p['filename']) ?>" alt="<?= esc($p['caption']) ?>" loading="lazy">
                        <div class="gallery-slide-overlay">
                            <span><?= esc($p['label']) ?></span>
                            <span class="zoom-hint">Click to zoom</span>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
            <div class="gallery-controls">
                <button class="gallery-btn" onclick="slideGallery(-1)" aria-label="Previous">&#10094;</button>
                <button class="gallery-btn" onclick="slideGallery(1)" aria-label="Next">&#10095;</button>
            </div>
        </div>
    </section>

    <!-- LIGHTBOX MODAL -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
        <button class="lightbox-close" onclick="closeLightbox(event)">&#10005;</button>
        <button class="lightbox-nav prev" onclick="navLightbox(event, -1)">&#10094;</button>
        <img id="lightboxImg" alt="">
        <button class="lightbox-nav next" onclick="navLightbox(event, 1)">&#10095;</button>
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-om">ॐ</div>
        <div class="footer-temple-name"><?= esc($set['footer_temple_name']) ?></div>
        <div class="footer-location"><?= esc($set['footer_location']) ?></div>
        <div class="footer-links">
<?php foreach ($nav as $l): ?>
            <a href="<?= esc($l['href']) ?>"><?= esc($l['label']) ?></a>
<?php endforeach; ?>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-copy" style="line-height: 1.9;"><?= clean_html($set['footer_credits']) ?></div>
        <div class="footer-copy" style="margin-top: 1rem;"><?= clean_html($set['footer_copyright']) ?></div>
    </footer>

    <!-- JAVASCRIPT -->
    <script>
        // Photo data comes from the database; the rest of the behaviour is static.
        const galleryImages = [
<?php foreach ($photos as $p): ?>
            { src: '<?= esc_js(IMAGE_URL . "/" . $p["filename"]) ?>', caption: '<?= esc_js($p["caption"]) ?>' },
<?php endforeach; ?>
        ];
    </script>
    <script src="assets/main.js?v=<?= filemtime(__DIR__ . "/assets/main.js") ?>"></script>

</body>
</html>
