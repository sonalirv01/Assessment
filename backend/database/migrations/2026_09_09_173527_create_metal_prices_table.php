<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_prices', function (Blueprint $table) {
            $table->id();
            // Machine key used by jewellery items, e.g. "gold_22k", "silver".
            $table->string('key')->unique();
            $table->string('label');
            // Today's market rate for this metal, per gram, in the store's base currency.
            $table->decimal('price_per_gram', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_prices');
    }
};
