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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Modificar campo existente
            $table->unsignedTinyInteger('shoe_size_eu')->nullable()->change();

            // Agregar nuevos campos
            $table->string('profile_image')->nullable()->after('shoe_size_eu');
            $table->text('bio')->nullable()->after('profile_image');
            $table->string('emergency_contact_name', 100)->nullable()->after('bio');
            $table->string('emergency_contact_phone', 15)->nullable()->after('emergency_contact_name');
            $table->text('medical_conditions')->nullable()->after('emergency_contact_phone')->comment('Condiciones médicas relevantes');
            $table->text('fitness_goals')->nullable()->after('medical_conditions');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            // Agregar nuevos índices
            $table->index(['first_name', 'last_name']);

            // Note: Check constraints will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropColumn([
                'profile_image', 'bio', 'emergency_contact_name',
                'emergency_contact_phone', 'medical_conditions', 'fitness_goals'
            ]);

            // Revertir cambio en campo existente
            $table->unsignedTinyInteger('shoe_size_eu')->nullable(false)->change();
        });
    }
};
