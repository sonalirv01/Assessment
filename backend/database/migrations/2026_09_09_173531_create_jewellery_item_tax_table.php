<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A single item can carry more than one tax (e.g. GST + a local cess),
        // so this is a plain many-to-many pivot with no extra columns of its own.
        Schema::create('jewellery_item_tax', function (Blueprint $table) {
            $table->foreignId('jewellery_item_id')->constrained('jewellery_items')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->primary(['jewellery_item_id', 'tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jewellery_item_tax');
    }
};
