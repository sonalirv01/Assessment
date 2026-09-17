/**
 * Exact decimal-string arithmetic backed by BigInt — the frontend mirror of
 * backend/app/Support/Decimal.php.
 *
 * The API returns money/weight fields as decimal strings (never numbers) for
 * the same reason this file exists: a JS `number` is a binary float, so
 * `0.1 + 0.2` doesn't equal `0.3`, and that drift compounds across a price
 * breakdown. Everything here works on base-10 strings via BigInt instead —
 * used for the client-side price *preview* before a save round-trips
 * through the backend, which remains the source of truth.
 */

const WORKING_SCALE = 10;

function pow10(scale: number): bigint {
  return 10n ** BigInt(scale);
}

function parseDecimal(value: string, scale: number): bigint {
  const trimmed = value.trim() || '0';
  const negative = trimmed.startsWith('-');
  const unsigned = negative ? trimmed.slice(1) : trimmed;
  const [wholePart, fractionPart = ''] = unsigned.split('.');
  const paddedFraction = (fractionPart + '0'.repeat(scale)).slice(0, scale);
  const digits = `${wholePart || '0'}${paddedFraction}`;
  const magnitude = BigInt(digits === '' ? '0' : digits);
  return negative ? -magnitude : magnitude;
}

function formatDecimal(scaled: bigint, scale: number): string {
  const negative = scaled < 0n;
  const magnitude = negative ? -scaled : scaled;
  const divisor = pow10(scale);
  const wholePart = (magnitude / divisor).toString();
  const fractionPart = (magnitude % divisor).toString().padStart(scale, '0');
  const body = scale > 0 ? `${wholePart}.${fractionPart}` : wholePart;
  return negative && magnitude !== 0n ? `-${body}` : body;
}

function toWorking(value: string): bigint {
  return parseDecimal(value, WORKING_SCALE);
}

/** Converts a raw form-control number (as typed, before any math) to a
 *  decimal string. Safe because it's stringifying an untouched user input,
 *  not the result of float arithmetic. */
export function fromInput(value: number | null | undefined): string {
  if (value === null || value === undefined || Number.isNaN(value)) {
    return '0';
  }
  return value.toString();
}

export function add(a: string, b: string): string {
  return formatDecimal(toWorking(a) + toWorking(b), WORKING_SCALE);
}

export function mul(a: string, b: string): string {
  const product = (toWorking(a) * toWorking(b)) / pow10(WORKING_SCALE);
  return formatDecimal(product, WORKING_SCALE);
}

export function div(a: string, b: string): string {
  const denominator = toWorking(b);
  if (denominator === 0n) {
    return formatDecimal(0n, WORKING_SCALE);
  }
  const numerator = toWorking(a) * pow10(WORKING_SCALE);
  return formatDecimal(numerator / denominator, WORKING_SCALE);
}

/** Round-half-up to `scale` digits — bigint division truncates toward zero,
 *  so half a unit is added at the last kept digit before truncating. */
export function round(value: string, scale = 2): string {
  const negative = value.trim().startsWith('-');
  const magnitude = negative ? value.trim().slice(1) : value.trim();
  const atWorkingScale = parseDecimal(magnitude, WORKING_SCALE);
  const divisor = pow10(WORKING_SCALE - scale);
  const roundedUnits = (atWorkingScale + divisor / 2n) / divisor;
  const formatted = formatDecimal(roundedUnits, scale);
  return negative && roundedUnits !== 0n ? `-${formatted}` : formatted;
}

export function compare(a: string, b: string): number {
  const diff = toWorking(a) - toWorking(b);
  return diff > 0n ? 1 : diff < 0n ? -1 : 0;
}

export function sum(values: string[], scale = 2): string {
  let total = '0';
  for (const value of values) {
    total = add(total, value);
  }
  return round(total, scale);
}

/** `base * (percentage / 100)`, rounded to `scale` digits. */
export function percentOf(base: string, percentage: string, scale = 2): string {
  return round(div(mul(base, percentage), '100'), scale);
}
