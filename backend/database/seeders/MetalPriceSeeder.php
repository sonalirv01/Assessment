<?php

namespace Database\Seeders;

use App\Models\MetalPrice;
use Illuminate\Database\Seeder;

class MetalPriceSeeder extends Seeder
{
    public function run(): void
    {
        // Sample rates per gram, roughly reflecting real karat differences.
        $rates = [
            ['key' => 'gold_24k', 'label' => 'Gold 24K', 'price_per_gram' => 7200],
            ['key' => 'gold_22k', 'label' => 'Gold 22K', 'price_per_gram' => 6600],
            ['key' => 'gold_18k', 'label' => 'Gold 18K', 'price_per_gram' => 5400],
            ['key' => 'silver', 'label' => 'Silver', 'price_per_gram' => 85],
            ['key' => 'platinum', 'label' => 'Platinum', 'price_per_gram' => 3100],
        ];

        foreach ($rates as $rate) {
            MetalPrice::updateOrCreate(['key' => $rate['key']], $rate);
        }
    }
}
