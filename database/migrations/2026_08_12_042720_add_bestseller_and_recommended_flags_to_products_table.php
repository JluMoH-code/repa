<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Ручное управление витринными блоками "Хиты продаж" / "Рекомендуем".
            // "Новинки" не нуждаются в отдельном флаге — берутся по created_at.
            $table->boolean('is_bestseller')->default(false)->after('is_discountable');
            $table->boolean('is_recommended')->default(false)->after('is_bestseller');

            $table->index('is_bestseller');
            $table->index('is_recommended');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_bestseller', 'is_recommended']);
        });
    }
};
