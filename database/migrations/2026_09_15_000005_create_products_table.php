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
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar')->index();
            $table->string('name_en')->nullable();
            $table->string('slug', 240)->nullable()->unique();
            $table->string('sku', 100)->nullable()->unique();
            $table->text('short_description_ar')->nullable();
            $table->text('short_description_en')->nullable();
            $table->longText('description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->foreignId('cover_media_id')->nullable()
                ->constrained('media')->nullOnDelete();
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('sale_price', 14, 2)->nullable();
            $table->string('currency', 10)->default('JOD')->index();
            $table->string('dimensions_ar', 255)->nullable();
            $table->string('dimensions_en', 255)->nullable();
            $table->string('material_ar', 150)->nullable();
            $table->string('material_en', 150)->nullable();
            $table->string('color_ar', 150)->nullable();
            $table->string('color_en', 150)->nullable();
            $table->enum('availability_status', ['available', 'sold_out', 'on_order'])
                ->default('available')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_new')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'is_new']);
            $table->index(['price', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};