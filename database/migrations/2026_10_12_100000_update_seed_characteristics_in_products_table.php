<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->change();
            $table->string('package_size')->nullable();
        });
    }

    public function down(): void
    {
        // Артикулы без значения остаются допустимыми, чтобы откат не терял товары.
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('package_size');
        });
    }
};
