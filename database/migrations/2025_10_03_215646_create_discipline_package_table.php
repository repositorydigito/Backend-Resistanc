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
        Schema::create('discipline_package', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discipline_id')->nullable()->constrained('disciplines')->onDelete('cascade')->comment('disciplina asociada');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('cascade')->comment('Paquete asociada');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_package');
    }
};
