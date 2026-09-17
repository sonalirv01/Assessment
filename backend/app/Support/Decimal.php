<?php

namespace App\Support;

/**
 * Exact decimal-string arithmetic backed by bcmath.
 *
 * Money and weight values must never pass through a PHP float — binary
 * float can't represent values like 0.1 exactly, so chained multiply/add
 * on floats silently drifts by fractions of a paisa. Every value here stays
 * a base-10 string from the database all the way to the JSON response;
 * bcmath operates on those strings directly and rounding only happens once,
 * explicitly, via round2().
 */
class Decimal
{
    /** Internal working scale, generous enough that intermediate bcmath
     *  operations (e.g. a percentage divide) don't truncate before the
     *  final round2() call. */
    private const WORKING_SCALE = 10;

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::WORKING_SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::WORKING_SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::WORKING_SCALE);
    }

    public static function div(string $a, string $b): string
    {
        return bcdiv($a, $b, self::WORKING_SCALE);
    }

    /** True when $a > $b. */
    public static function gt(string $a, string $b): bool
    {
        return bccomp($a, $b, self::WORKING_SCALE) === 1;
    }

    /** -1, 0, or 1 — same contract as the spaceship operator, for sorting. */
    public static function compare(string $a, string $b): int
    {
        return bccomp($a, $b, self::WORKING_SCALE);
    }

    /**
     * Round a decimal string to $scale digits using round-half-up, the
     * convention jewellers/retailers expect on money (bcmath's own scale
     * truncation is round-towards-zero, not round-half-up, so this can't
     * just be bcadd($value, '0', $scale)).
     */
    public static function round(string $value, int $scale = 2): string
    {
        $negative = str_starts_with($value, '-');
        $unsigned = $negative ? substr($value, 1) : $value;

        $halfUlp = bcdiv('5', bcpow('10', (string) ($scale + 1)), self::WORKING_SCALE);
        $rounded = bcadd($unsigned, $halfUlp, $scale);

        $result = $negative && bccomp($rounded, '0', $scale) !== 0 ? "-{$rounded}" : $rounded;

        return $result;
    }

    /** Sum a list of decimal strings, then round once at the end. */
    public static function sum(array $values, int $scale = 2): string
    {
        $total = '0';
        foreach ($values as $value) {
            $total = self::add($total, (string) $value);
        }

        return self::round($total, $scale);
    }
}
