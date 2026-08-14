<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filter_value_product', function (Blueprint $table) {
            $table->foreignId('filter_value_id')->constrained('filter_values')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->primary(['filter_value_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_value_product');
    }
};
