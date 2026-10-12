<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'product_variants'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedInteger('stock_quantity')->nullable();
                $table->unsignedInteger('reserved_quantity')->default(0);
            });
        }
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
        });
        // Partial indexes also enforce the legacy line (NULL variant) uniqueness.
        DB::statement('CREATE UNIQUE INDEX carts_base_unique ON carts (user_id, product_id) WHERE product_variant_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX carts_variant_unique ON carts (user_id, product_id, product_variant_id) WHERE product_variant_id IS NOT NULL');
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variant_label')->nullable();
            $table->unsignedInteger('reserved_quantity')->default(0);
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type');
            $table->integer('quantity_change');
            $table->integer('reserved_change')->default(0);
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['variant_label', 'reserved_quantity']);
        });
        DB::statement('DROP INDEX carts_base_unique');
        DB::statement('DROP INDEX carts_variant_unique');
        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['user_id', 'product_id']);
        });
        foreach (['products', 'product_variants'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['stock_quantity', 'reserved_quantity']));
        }
    }
};
