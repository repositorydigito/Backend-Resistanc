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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->string('title')->comment('Titulo');
            $table->string('slug')->unique()->comment('slug');
            $table->string('image_path')->nullable()->comment('Imagen principal');
            $table->longText('content')->nullable()->comment('contenido del articulo');
            $table->boolean('is_featured')->default(false)->comment('es destacado');

            $table->enum('status', ['draft', 'published', 'Dismissed'])->default('draft');

            $table->date('date_published')->nullable();

            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
