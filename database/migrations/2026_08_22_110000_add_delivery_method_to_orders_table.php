<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Способ получения заказа: самовывоз (pickup) / доставка по адресу (delivery).
            $table->string('delivery_method', 20)->default('pickup')->after('comment');
            // Служба доставки (Почта России, СДЭК, Яндекс...). На текущем этапе
            // доставка в разработке — поле зарезервировано и не заполняется.
            $table->string('delivery_service', 60)->nullable()->after('delivery_method');

            // Адрес доставки не обязателен для самовывоза.
            $table->string('delivery_city', 120)->nullable()->change();
            $table->string('delivery_postcode', 10)->nullable()->change();
            $table->string('delivery_address', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'delivery_service']);

            $table->string('delivery_city', 120)->nullable(false)->change();
            $table->string('delivery_postcode', 10)->nullable(false)->change();
            $table->string('delivery_address', 255)->nullable(false)->change();
        });
    }
};
