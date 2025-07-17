<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nombre de la categoría del producto');
            $table->string('slug', 100)->unique()->comment('Slug de la categoría del producto');
            $table->text('description')->nullable()->comment('Descripción de la categoría del producto');
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->onDelete('set null')->comment('Categoría padre');
            $table->string('image_url')->nullable()->comment('URL de la imagen de la categoría del producto');
            $table->boolean('is_active')->default(true)->comment('Indica si la categoría está activa');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('Orden de clasificación de la categoría');

            // Índices
            $table->index(['parent_id', 'is_active']);
            $table->index('slug');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
