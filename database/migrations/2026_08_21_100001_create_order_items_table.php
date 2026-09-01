<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // restrictOnDelete: товар нельзя удалить, пока он присутствует
            // хотя бы в одном заказе (см. guard в App\Models\Product::deleting).
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            // Снимок имени и цены на момент заказа: товар могут переименовать
            // или поменять цену, но в распечатке заказа должны остаться старые значения.
            $table->string('product_name', 255);
            $table->unsignedBigInteger('price'); // копейки
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total'); // копейки
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
