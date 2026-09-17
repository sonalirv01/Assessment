<?php

namespace Tests\Unit;

use App\Support\Decimal;
use Tests\TestCase;

class DecimalTest extends TestCase
{
    public function test_add_matches_exact_decimal_arithmetic(): void
    {
        $this->assertSame('0.3000000000', Decimal::add('0.1', '0.2'));
    }

    public function test_repeated_addition_never_drifts_unlike_float(): void
    {
        // The float equivalent of this loop (0.0 += 0.1, a thousand times)
        // lands on 100.00000000000001, not 100 — the exact bug this class
        // exists to rule out. Confirm the float drift actually happens...
        $floatSum = 0.0;
        for ($i = 0; $i < 1000; $i++) {
            $floatSum += 0.1;
        }
        $this->assertNotSame(100.0, $floatSum);

        // ...then confirm Decimal::sum() doesn't have the same problem.
        $decimalSum = Decimal::sum(array_fill(0, 1000, '0.10'));
        $this->assertSame('100.00', $decimalSum);
    }

    public function test_round_half_up(): void
    {
        $this->assertSame('41.67', Decimal::round('41.66625'));
        $this->assertSame('2.01', Decimal::round('2.005'));
        $this->assertSame('2.00', Decimal::round('2.004'));
        $this->assertSame('0.00', Decimal::round('0.001'));
    }

    public function test_mul_and_div(): void
    {
        $this->assertSame('333.3300000000', Decimal::mul('333.33', '1.00'));
        $this->assertSame('41.6662500000', Decimal::div(Decimal::mul('333.33', '12.5'), '100'));
    }

    public function test_compare(): void
    {
        $this->assertSame(-1, Decimal::compare('9.00', '10.00'));
        $this->assertSame(1, Decimal::compare('10.00', '9.00'));
        $this->assertSame(0, Decimal::compare('10.00', '10.00'));
    }

    public function test_sum_rounds_once_at_the_end(): void
    {
        $this->assertSame('41.67', Decimal::sum(['20.835', '20.83125']));
    }
}
