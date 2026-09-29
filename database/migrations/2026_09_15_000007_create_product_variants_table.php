<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('sale_price', 14, 2)->nullable();
            $table->string('dimensions_ar', 255)->nullable();
            $table->string('dimensions_en', 255)->nullable();
            $table->string('material_ar', 150)->nullable();
            $table->string('material_en', 150)->nullable();
            $table->string('color_ar', 150)->nullable();
            $table->string('color_en', 150)->nullable();
            $table->integer('stock_qty')->default(0);
            $table->foreignId('media_id')->nullable()
                ->constrained('media')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};