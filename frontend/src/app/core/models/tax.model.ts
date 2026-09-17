export interface Tax {
  id: number;
  name: string;
  // Exact decimal string from the API — see core/utils/decimal.util.ts.
  percentage: string;
  is_active: boolean;
}
