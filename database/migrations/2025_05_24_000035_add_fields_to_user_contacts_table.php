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
        Schema::table('user_contacts', function (Blueprint $table) {
            // Modificar campos existentes
            $table->string('city', 100)->nullable(false)->change();
            $table->string('country', 100)->default('Peru')->change();
            
            // Agregar nuevos campos
            $table->string('state_province', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('country');
            $table->enum('contact_type', ['home', 'work', 'emergency', 'other'])->default('home')->after('postal_code');
            $table->boolean('is_billing_address')->default(false)->after('is_primary');
        });

        Schema::table('user_contacts', function (Blueprint $table) {
            // Agregar nuevos índices
            $table->index(['city', 'country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_contacts', function (Blueprint $table) {
            $table->dropIndex(['city', 'country']);
            $table->dropColumn(['state_province', 'postal_code', 'contact_type', 'is_billing_address']);
            
            // Revertir cambios en campos existentes
            $table->string('city', 80)->nullable()->change();
            $table->char('country', 2)->default('PE')->change();
        });
    }
};
