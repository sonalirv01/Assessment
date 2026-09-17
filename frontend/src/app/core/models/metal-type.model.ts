export interface MetalType {
  key: string;
  label: string;
  // Exact decimal string from the API — see core/utils/decimal.util.ts.
  price_per_gram: string;
  updated_at: string;
}
