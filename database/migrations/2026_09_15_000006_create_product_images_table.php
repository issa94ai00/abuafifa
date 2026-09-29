<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('alt_ar', 255)->nullable();
            $table->string('alt_en', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'media_id']);
            $table->index(['product_id', 'sort_order', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};