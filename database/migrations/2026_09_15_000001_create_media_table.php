<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 30)->default('public')->index();
            $table->string('collection', 60)->index();
            $table->string('directory', 200);
            $table->string('filename', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_ar', 255)->nullable();
            $table->string('alt_en', 255)->nullable();
            $table->string('caption', 255)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->index(['collection', 'disk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};