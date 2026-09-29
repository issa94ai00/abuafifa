<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar')->index();
            $table->string('title_en')->nullable();
            $table->string('slug', 240)->nullable()->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('image_media_id')->nullable()
                ->constrained('media')->nullOnDelete();
            $table->enum('discount_type', ['percentage', 'fixed', 'custom_price'])
                ->default('percentage');
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->decimal('price_override', 14, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};