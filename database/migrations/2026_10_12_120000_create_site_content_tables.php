<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_pages', function (Blueprint $t) {
            $t->id();
            $t->string('kind');
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('excerpt')->nullable();
            $t->text('body');
            $t->string('status')->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->string('seo_title')->nullable();
            $t->text('seo_description')->nullable();
            $t->timestamps();
            $t->index(['kind', 'status', 'published_at']);
        });
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('subtitle')->nullable();
            $t->string('image')->nullable();
            $t->string('url')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('menu_links', function (Blueprint $t) {
            $t->id();
            $t->string('label');
            $t->string('url');
            $t->string('location')->default('header');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_links');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('content_pages');
    }
};
