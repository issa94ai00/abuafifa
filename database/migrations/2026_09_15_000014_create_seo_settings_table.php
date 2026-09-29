<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 160)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('route_name', 160)->nullable()->unique();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_media_id')->nullable()
                ->constrained('media')->nullOnDelete();
            $table->string('canonical_url', 500)->nullable();
            $table->string('robots', 60)->default('index,follow');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};