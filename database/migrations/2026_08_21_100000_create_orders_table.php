<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Человекочитаемый номер вида «Р-2026-000123», генерируется в модели.
            $table->string('number', 20)->unique();
            // nullOnDelete: гостевой заказ остаётся в истории после удаления аккаунта.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 120);
            $table->string('customer_email', 180);
            $table->string('customer_phone', 20);
            $table->string('delivery_city', 120);
            $table->string('delivery_postcode', 10)->nullable();
            $table->string('delivery_address', 255);
            $table->text('comment')->nullable();
            $table->string('status', 20); // enum App\Enums\OrderStatus
            $table->unsignedBigInteger('subtotal'); // копейки
            $table->unsignedBigInteger('total');    // копейки (на этом этапе = subtotal, без доставки)
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('placed_at');
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
