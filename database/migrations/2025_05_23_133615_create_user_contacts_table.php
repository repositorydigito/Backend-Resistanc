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
        Schema::create('user_contacts', function (Blueprint $table) {
            $table->id();

            $table->string('phone', 15)->comment('Telefono');
            $table->string('address_line', 255)->comment('Direccion');
            $table->string('city', 100)->nullable(false)->comment('Ciudad');
            $table->string('state_province', 100)->nullable()->comment('Estado o Provincia');
            $table->string('country', 100)->default('Peru')->comment('Pais');
            $table->string('postal_code', 20)->nullable()->comment('Código Postal');
            $table->enum('contact_type', ['home', 'work', 'emergency', 'other'])->default('home')->comment('tipo de contacto');
            $table->boolean('is_primary')->default(true)->comment('Si es primario');
            $table->boolean('is_billing_address')->default(false)->comment('Es direccion de facturacion');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index(['user_id', 'is_primary']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contacts');
    }
};
