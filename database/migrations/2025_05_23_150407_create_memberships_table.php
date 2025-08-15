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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique()->comment('Nombre de la membresía');
            $table->string('slug')->unique()->comment('Unique identificador de la membresía');
            $table->integer('level')->unique()->comment('Nivel de la membresía');

            $table->text('description')->nullable()->comment('Descripción de la membresía');
            $table->integer('classes_before')->default(0)->comment('Número de dias que puede reservar clases antes de la publicacion de la clase');

            $table->integer('duration')->default(3)->comment('Duración de la membresía en meses');


            $table->string('color_hex')->nullable()->comment('Color hexadecimal asociado a la membresía');
            $table->json('colors')->nullable()->comment('Colores asociados a la membresía');
            $table->string('icon')->nullable()->comment('Ícono asociado a la membresía');


            // Shake
            $table->boolean('is_benefit_shake')->default(false)
                ->comment('Indica si la membresía incluye beneficios para el shake');

            $table->integer('shake_quantity')->default(1)
                ->comment('Cantidad de shakes incluidos en la membresía');
            // Fin shake

            // Disciplinas
            $table->boolean('is_benefit_discipline')->default(false)
                ->comment('Indica si la membresía incluye beneficios para la disciplina');
            $table->foreignId('discipline_id')
                ->nullable()
                ->constrained('disciplines')
                ->nullOnDelete()
                ->comment('Disciplina asociada al beneficio de disciplina');

            $table->integer('discipline_quantity')->default(1)
                ->comment('Cantidad de disciplinas incluidas en la membresía');
            // Fin disciplinas

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
