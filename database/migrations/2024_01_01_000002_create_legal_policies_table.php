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
            $table->enum('type', ['privacy', 'term'])->comment('Politicas y terminos'); // privacy = Políticas de Privacidad, terms = Términos y Condiciones
            $table->string('title')->comment('Titulo del termino o politica');
            $table->longText('content')->nullable()->comment('Campo obsoleto - no se usa en el formulario');
            $table->boolean('is_active')->default(true)->comment('Es activo o no esta activo ');

            $table->timestamps();

            $table->index('type');
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
