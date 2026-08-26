// Mirrors includes/collections.php's shape, served by GET /api/config.php.

export type FieldType = 'text' | 'area' | 'rich' | 'emoji' | 'image';

export interface FieldDef {
  name: string;
  label: string;
  type: FieldType;
  required?: boolean;
  hint?: string;
}

export interface ListDef {
  label: string;
  summary: string;
  fields: FieldDef[];
}

export interface CollectionDef {
  label: string;
  icon: string;
  section?: string;
  lists: Record<string, ListDef>;
}

export type CollectionsConfig = Record<string, CollectionDef>;

export interface SettingValue {
  svalue: string;
  notes: string;
  type: FieldType;
}

export interface ConfigResponse {
  collections: CollectionsConfig;
  setting_groups: Record<string, string[]>;
  setting_types: Record<string, FieldType>;
}

// A row from any of the seven list collections - field values are always
// strings server-side, plus the two fields db.php's _doc() always adds.
export type Row = Record<string, string> & { id: string; sort_order: number };

export interface SectionHeading {
  title_ta: string;
  title_en: string;
}

export interface RowsResponse {
  section: SectionHeading | null;
  lists: Record<string, Row[]>;
}

export interface DashboardSection {
  key: string;
  label: string;
  icon: string;
  count: number;
}

export interface DashboardResponse {
  sections: DashboardSection[];
  gallery_count: number;
}

export interface SettingsResponse {
  groups: Record<string, string[]>;
  values: Record<string, SettingValue>;
}

export interface GalleryPhoto {
  id: string;
  filename: string;
  label: string;
  caption: string;
  sort_order: number;
  uploaded_at: string;
  has_thumb: boolean;
  url: string;
  thumb_url: string;
}
