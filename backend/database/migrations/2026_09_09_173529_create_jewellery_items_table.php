<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jewellery_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            // References metal_prices.key rather than its id, so the catalogue reads
            // naturally (e.g. "gold_22k") and metal rates can be reseeded freely.
            $table->string('metal_type');
            $table->foreign('metal_type')->references('key')->on('metal_prices');
            $table->decimal('weight_grams', 10, 3);
            $table->decimal('making_charges', 12, 2)->default(0);
            $table->decimal('shipping_charges', 12, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jewellery_items');
    }
};
