export interface ValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}

export interface ApiErrorResponse {
  message: string;
}
