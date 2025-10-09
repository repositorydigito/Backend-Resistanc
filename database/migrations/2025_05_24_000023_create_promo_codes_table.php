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


        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Nombre de la promocion');
            $table->string('name_supplier')->nullable()->comment('Nombre del proveedor');
            $table->string('initial')->comment('Inicial del codigo');
            $table->string('code')->comment('Codigo combinado con el codigo');

            $table->enum('type', ['season', 'consumption'])->default('consumption')->comment('Tipo: temporada o consumo');
            $table->dateTime('start_date')->nullable()->comment('Fecha de inicio para promociones por temporada');
            $table->dateTime('end_date')->nullable()->comment('Fecha de fin para promociones por temporada');


            $table->enum('status', ['active', 'inactive']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
