<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $taxes = [
            ['name' => 'GST', 'percentage' => 3, 'is_active' => true],
            ['name' => 'Making Charge Service Tax', 'percentage' => 1, 'is_active' => true],
        ];

        foreach ($taxes as $tax) {
            Tax::updateOrCreate(['name' => $tax['name']], $tax);
        }
    }
}
