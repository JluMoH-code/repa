<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();

            $table->text('short_description')->nullable();
            $table->text('description')->nullable();

            // Цены — в копейках, чтобы избежать проблем с плавающей точкой.
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('old_price')->nullable();

            $table->string('unit')->default('упаковка');
            $table->integer('seed_count')->nullable();

            $table->enum('status', ['draft', 'published', 'hidden', 'archived', 'discontinued', 'preorder'])
                ->default('draft');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_discountable')->default(true);

            // Фиксированные атрибуты для быстрой фильтрации — отдельные колонки с индексами.
            $table->string('culture')->nullable();
            $table->enum('ripening', ['early', 'mid', 'late'])->nullable();
            $table->enum('growing_place', ['open_ground', 'greenhouse', 'universal'])->nullable();
            $table->boolean('is_hybrid')->nullable();
            $table->string('series')->nullable();

            // Остальные, нефильтруемые характеристики — свободная схема.
            $table->jsonb('attributes')->nullable();

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('culture');
            $table->index('ripening');
            $table->index('growing_place');
            $table->index('is_hybrid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
