<?php
/**
 * Describes every editable part of the page.
 *
 * All seven list tables have the same shape (id, text columns, sort_order), so
 * one generic editor in admin/edit.php reads this and renders the right form.
 * Adding a field to a section means adding a line here, not writing a new page.
 *
 * Field types:
 *   text     single-line, escaped on output
 *   area     multi-line, escaped on output
 *   rich     multi-line, a small set of tags allowed (see sanitize.php)
 *   emoji    single-line, shown narrow
 *   image    filename in /images, with an upload/pick control
 */
declare(strict_types=1);

function collections(): array
{
    return [
        'nav' => [
            'label' => 'மெனு / Navigation',
            'icon'  => '☰',
            'lists' => [
                'nav_links' => [
                    'label'   => 'மெனு இணைப்புகள் / Menu links',
                    'summary' => 'label',
                    'fields'  => [
                        ['name' => 'label', 'label' => 'பெயர் / Label', 'type' => 'text', 'required' => true],
                        ['name' => 'href',  'label' => 'இணைப்பு / Anchor', 'type' => 'text', 'required' => true,
                         'hint' => 'Must start with # — e.g. #about'],
                    ],
                ],
            ],
        ],

        'about' => [
            'label'   => 'கோயில் பற்றி / About',
            'icon'    => 'ℹ️',
            'section' => 'about',
            'lists'   => [
                'about_paragraphs' => [
                    'label'   => 'பத்திகள் / Paragraphs',
                    'summary' => 'body_ta',
                    'fields'  => [
                        ['name' => 'body_ta', 'label' => 'பத்தி (தமிழ்)', 'type' => 'rich', 'required' => true],
                        ['name' => 'body_en', 'label' => 'Paragraph (English)', 'type' => 'rich', 'required' => true],
                    ],
                ],
                'about_rows' => [
                    'label'   => 'தகவல் அட்டவணை / Info table',
                    'summary' => 'label',
                    'fields'  => [
                        ['name' => 'label',    'label' => 'தலைப்பு / Label', 'type' => 'text', 'required' => true],
                        ['name' => 'value_ta', 'label' => 'மதிப்பு (தமிழ்)', 'type' => 'rich', 'required' => true],
                        ['name' => 'value_en', 'label' => 'Value (English)', 'type' => 'rich'],
                    ],
                ],
            ],
        ],

        'history' => [
            'label'   => 'வரலாறு / History',
            'icon'    => '📜',
            'section' => 'history',
            'lists'   => [
                'history_entries' => [
                    'label'   => 'பதிவுகள் / Entries',
                    'summary' => 'title_ta',
                    'fields'  => [
                        ['name' => 'title_ta', 'label' => 'தலைப்பு (தமிழ்)', 'type' => 'text', 'required' => true],
                        ['name' => 'title_en', 'label' => 'Title (English)', 'type' => 'text', 'required' => true],
                        ['name' => 'body_ta',  'label' => 'விவரம் (தமிழ்)', 'type' => 'rich', 'required' => true],
                        ['name' => 'body_en',  'label' => 'Description (English)', 'type' => 'rich', 'required' => true],
                    ],
                ],
            ],
        ],

        'deities' => [
            'label'   => 'தெய்வங்கள் / Deities',
            'icon'    => '🔱',
            'section' => 'deities',
            'lists'   => [
                'deity_cards' => [
                    'label'   => 'தெய்வ அட்டைகள் / Deity cards',
                    'summary' => 'name_ta',
                    'fields'  => [
                        ['name' => 'icon',    'label' => 'சின்னம் / Icon', 'type' => 'emoji',
                         'hint' => 'A single emoji, e.g. 🔱'],
                        ['name' => 'name_ta', 'label' => 'பெயர் (தமிழ்)', 'type' => 'text', 'required' => true],
                        ['name' => 'name_en', 'label' => 'Name (English)', 'type' => 'text', 'required' => true],
                        ['name' => 'desc_ta', 'label' => 'விவரம் (தமிழ்)', 'type' => 'rich', 'required' => true],
                        ['name' => 'desc_en', 'label' => 'Description (English)', 'type' => 'rich', 'required' => true],
                    ],
                ],
            ],
        ],

        'festivals' => [
            'label'   => 'திருவிழாக்கள் / Festivals',
            'icon'    => '🪔',
            'section' => 'festivals',
            'lists'   => [
                'festival_items' => [
                    'label'   => 'விழாக்கள் / Festival entries',
                    'summary' => 'title_ta',
                    'fields'  => [
                        ['name' => 'month_ta', 'label' => 'மாதம் (தமிழ்)', 'type' => 'text', 'required' => true],
                        ['name' => 'month_en', 'label' => 'Month (English)', 'type' => 'rich', 'required' => true,
                         'hint' => 'Use &lt;br&gt; for a second line, e.g. Masi&lt;br&gt;(Feb-Mar)'],
                        ['name' => 'title_ta', 'label' => 'விழா பெயர் (தமிழ்)', 'type' => 'text', 'required' => true],
                        ['name' => 'title_en', 'label' => 'Festival name (English)', 'type' => 'text', 'required' => true],
                        ['name' => 'body_ta',  'label' => 'விவரம் (தமிழ்)', 'type' => 'rich', 'required' => true],
                        ['name' => 'body_en',  'label' => 'Description (English)', 'type' => 'rich', 'required' => true],
                    ],
                ],
            ],
        ],

        'location' => [
            'label'   => 'இடம் / Location',
            'icon'    => '📍',
            'section' => 'location',
            'lists'   => [
                'location_items' => [
                    'label'   => 'இட விவரங்கள் / Location details',
                    'summary' => 'label',
                    'fields'  => [
                        ['name' => 'icon',     'label' => 'சின்னம் / Icon', 'type' => 'emoji'],
                        ['name' => 'label',    'label' => 'தலைப்பு / Label', 'type' => 'text', 'required' => true],
                        ['name' => 'value_ta', 'label' => 'மதிப்பு (தமிழ்)', 'type' => 'rich', 'required' => true,
                         'hint' => 'Links are allowed here.'],
                        ['name' => 'value_en', 'label' => 'Value (English)', 'type' => 'rich'],
                    ],
                ],
            ],
        ],

        'visit' => [
            'label'   => 'வருகை தகவல் / Visitor Info',
            'icon'    => '🕐',
            'section' => 'visit',
            'lists'   => [
                'visit_cards' => [
                    'label'   => 'அட்டைகள் / Cards',
                    'summary' => 'title_ta',
                    'fields'  => [
                        ['name' => 'icon',     'label' => 'சின்னம் / Icon', 'type' => 'emoji'],
                        ['name' => 'title_ta', 'label' => 'தலைப்பு (தமிழ்)', 'type' => 'text', 'required' => true],
                        ['name' => 'title_en', 'label' => 'Title (English)', 'type' => 'text', 'required' => true],
                        ['name' => 'body',     'label' => 'உள்ளடக்கம் / Content', 'type' => 'rich', 'required' => true,
                         'hint' => 'Allowed: &lt;br&gt;, &lt;em&gt;, &lt;span class="time"&gt;…&lt;/span&gt;, links'],
                    ],
                ],
            ],
        ],
    ];
}

/** One collection by key, or null. */
function collection(string $key): ?array
{
    return collections()[$key] ?? null;
}

/**
 * Settings grouped for the settings screen, so the admin sees related fields
 * together rather than one long undifferentiated list.
 */
function setting_groups(): array
{
    return [
        'கோயில் தகவல் / Temple details' => [
            'brand_ta', 'phone_display', 'phone_link', 'latitude', 'longitude',
        ],
        'முதல் பகுதி / Hero section' => [
            'hero_image', 'hero_image_alt', 'hero_title_ta', 'hero_title_en',
            'hero_subtitle_ta', 'hero_subtitle_en', 'hero_scroll_hint',
        ],
        'தேடுபொறி & பகிர்வு / Search & sharing' => [
            'title_tag', 'meta_description', 'og_site_name', 'og_title',
            'og_description', 'twitter_description', 'og_url', 'og_image',
        ],
        'அடிப்பகுதி / Footer' => [
            'footer_temple_name', 'footer_location', 'footer_credits', 'footer_copyright',
        ],
    ];
}

/** Which settings keys hold markup, an image, or a long block of text. */
function setting_type(string $key): string
{
    if ($key === 'hero_image' || $key === 'og_image') {
        return $key === 'hero_image' ? 'image' : 'text';
    }
    if (in_array($key, ['brand_ta', 'hero_title_ta', 'footer_credits', 'footer_copyright'], true)) {
        return 'rich';
    }
    if (in_array($key, ['meta_description', 'og_description', 'twitter_description'], true)) {
        return 'area';
    }
    return 'text';
}
