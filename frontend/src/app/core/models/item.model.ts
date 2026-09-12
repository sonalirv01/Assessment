export interface ItemCategoryRef {
  id: number;
  name: string;
}

export interface ItemTaxRef {
  id: number;
  name: string;
  percentage: number;
}

export interface TaxLine {
  tax_id: number;
  name: string;
  percentage: number;
  amount: number;
}

export interface PriceBreakdown {
  metal_rate_per_gram: number;
  metal_cost: number;
  making_charges: number;
  taxable_amount: number;
  tax_lines: TaxLine[];
  tax_total: number;
  shipping_charges: number;
  final_price: number;
}

export interface ItemImage {
  id: number;
  url: string;
  sort_order: number;
}

export interface JewelleryItem {
  id: number;
  name: string;
  description: string;
  category: ItemCategoryRef;
  metal_type: string;
  metal_type_label: string;
  weight_grams: number;
  making_charges: number;
  shipping_charges: number;
  is_available: boolean;
  images: ItemImage[];
  taxes: ItemTaxRef[];
  price_breakdown: PriceBreakdown;
  created_at: string;
  updated_at: string;
}

export interface ItemsMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ItemsResponse {
  data: JewelleryItem[];
  meta: ItemsMeta;
}

export interface ItemPayload {
  name: string;
  description: string;
  category_id: number;
  metal_type: string;
  weight_grams: number;
  making_charges: number;
  shipping_charges: number;
  is_available: boolean;
  tax_ids: number[];
}

export type SortField = 'name' | 'price';
export type SortDirection = 'asc' | 'desc';

export interface ItemsQueryParams {
  search?: string;
  category_id?: number;
  metal_type?: string;
  min_price?: number;
  max_price?: number;
  is_available?: boolean;
  sort_by?: SortField;
  sort_dir?: SortDirection;
  page?: number;
  per_page?: number;
}
