<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filter_group_id')->constrained('filter_groups')->cascadeOnDelete();
            $table->string('value');
            $table->string('slug');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['filter_group_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_values');
    }
};
