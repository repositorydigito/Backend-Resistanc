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
        Schema::create('legal_policies', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['privacy', 'terms']); // privacy = Políticas de Privacidad, terms = Términos y Condiciones
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('content')->nullable()->comment('Campo obsoleto - no se usa en el formulario');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->unique(['type', 'is_active']); // Solo una política activa por tipo
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_policies');
    }
};