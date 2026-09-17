<?php

namespace Tests\Unit;

use App\Models\JewelleryItem;
use App\Models\MetalPrice;
use App\Models\Tax;
use App\Services\JewelleryPriceCalculator;
use Tests\TestCase;

class JewelleryPriceCalculatorTest extends TestCase
{
    private function makeItem(array $attributes, MetalPrice $metalPrice, array $taxes = []): JewelleryItem
    {
        $item = new JewelleryItem($attributes);
        $item->setRelation('metalPrice', $metalPrice);
        $item->setRelation('taxes', collect($taxes));

        return $item;
    }

    private function makeTax(int $id, string $name, string $percentage): Tax
    {
        $tax = new Tax(['name' => $name, 'percentage' => $percentage]);
        $tax->id = $id;

        return $tax;
    }

    public function test_metal_cost_is_weight_times_rate(): void
    {
        $item = $this->makeItem(
            ['weight_grams' => '10.000', 'making_charges' => '0.00', 'shipping_charges' => '0.00'],
            new MetalPrice(['key' => 'gold_22k', 'label' => 'Gold 22K', 'price_per_gram' => '6000.00'])
        );

        $breakdown = (new JewelleryPriceCalculator)->calculate($item);

        $this->assertSame('60000.00', $breakdown['metal_cost']);
    }

    public function test_shipping_charges_are_included_in_the_taxable_amount(): void
    {
        // Regression guard: shipping used to be added *after* tax, untaxed.
        // It must now be part of what tax is calculated on.
        $item = $this->makeItem(
            ['weight_grams' => '10.000', 'making_charges' => '500.00', 'shipping_charges' => '100.00'],
            new MetalPrice(['key' => 'gold_22k', 'label' => 'Gold 22K', 'price_per_gram' => '6000.00']),
            [$this->makeTax(1, 'CGST', '9.00'), $this->makeTax(2, 'SGST', '9.00')]
        );

        $breakdown = (new JewelleryPriceCalculator)->calculate($item);

        // metal (60000) + making (500) + shipping (100) = 60600
        $this->assertSame('60600.00', $breakdown['taxable_amount']);
        // 9% of 60600 = 5454.00, twice
        $this->assertSame('5454.00', $breakdown['tax_lines'][0]['amount']);
        $this->assertSame('5454.00', $breakdown['tax_lines'][1]['amount']);
        $this->assertSame('10908.00', $breakdown['tax_total']);
        $this->assertSame('71508.00', $breakdown['final_price']);
    }

    public function test_tax_rounds_half_up_not_down(): void
    {
        $item = $this->makeItem(
            ['weight_grams' => '1.000', 'making_charges' => '30.00', 'shipping_charges' => '3.33'],
            new MetalPrice(['key' => 'silver', 'label' => 'Silver', 'price_per_gram' => '300.00']),
            [$this->makeTax(1, 'Luxury tax', '12.5')]
        );

        $breakdown = (new JewelleryPriceCalculator)->calculate($item);

        // taxable = 300 + 30 + 3.33 = 333.33; 12.5% of that is 41.66625,
        // which rounds up to 41.67, not down to 41.66.
        $this->assertSame('333.33', $breakdown['taxable_amount']);
        $this->assertSame('41.67', $breakdown['tax_lines'][0]['amount']);
        $this->assertSame('375.00', $breakdown['final_price']);
    }

    public function test_item_with_no_taxes_has_zero_tax_total(): void
    {
        $item = $this->makeItem(
            ['weight_grams' => '5.000', 'making_charges' => '200.00', 'shipping_charges' => '50.00'],
            new MetalPrice(['key' => 'gold_22k', 'label' => 'Gold 22K', 'price_per_gram' => '6000.00'])
        );

        $breakdown = (new JewelleryPriceCalculator)->calculate($item);

        $this->assertSame('0.00', $breakdown['tax_total']);
        $this->assertSame($breakdown['taxable_amount'], $breakdown['final_price']);
    }
}
