/** Mirrors api/public.php's response shape exactly. */

export interface SiteSettings {
  title_tag: string;
  meta_description: string;
  og_site_name: string;
  og_title: string;
  og_description: string;
  twitter_description: string;
  og_url: string;
  og_image: string;
  brand_ta: string;
  phone_display: string;
  phone_link: string;
  latitude: string;
  longitude: string;
  hero_image: string;
  hero_image_url: string;
  hero_image_alt: string;
  hero_title_ta: string;
  hero_title_en: string;
  hero_subtitle_ta: string;
  hero_subtitle_en: string;
  hero_scroll_hint: string;
  footer_temple_name: string;
  footer_location: string;
  footer_credits: string;
  footer_copyright: string;
  [key: string]: string;
}

export interface SectionHeading {
  title_ta: string;
  title_en: string;
}

/** Every row from a collections.php-backed list carries these two fields plus its own. */
interface RowBase {
  id: string;
  sort_order: number;
}

export interface NavLink extends RowBase {
  label: string;
  href: string;
}

export interface AboutParagraph extends RowBase {
  body_ta: string;
  body_en: string;
}

export interface AboutRow extends RowBase {
  label: string;
  value_ta: string;
  value_en?: string;
}

export interface HistoryEntry extends RowBase {
  title_ta: string;
  title_en: string;
  body_ta: string;
  body_en: string;
}

export interface DeityCard extends RowBase {
  icon: string;
  name_ta: string;
  name_en: string;
  desc_ta: string;
  desc_en: string;
}

export interface FestivalItem extends RowBase {
  month_ta: string;
  month_en: string;
  title_ta: string;
  title_en: string;
  body_ta: string;
  body_en: string;
}

export interface SpecialDayItem extends RowBase {
  month_ta: string;
  month_en: string;
  date_label: string;
  title_ta: string;
  title_en: string;
  body_ta: string;
  body_en: string;
}

export interface LocationItem extends RowBase {
  icon: string;
  label: string;
  value_ta: string;
  value_en?: string;
}

export interface VisitCard extends RowBase {
  icon: string;
  title_ta: string;
  title_en: string;
  body: string;
}

export interface GalleryPhoto extends RowBase {
  filename: string;
  label: string;
  caption: string;
  uploaded_at: string | null;
  url: string;
  thumb_url: string;
  has_thumb: boolean;
}

export interface PublicContent {
  settings: SiteSettings;
  sections: Record<string, SectionHeading>;
  nav_links: NavLink[];
  about_paragraphs: AboutParagraph[];
  about_rows: AboutRow[];
  history_entries: HistoryEntry[];
  deity_cards: DeityCard[];
  festival_items: FestivalItem[];
  special_day_items: SpecialDayItem[];
  location_items: LocationItem[];
  visit_cards: VisitCard[];
  gallery_photos: GalleryPhoto[];
}
