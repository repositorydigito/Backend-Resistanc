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
        Schema::table('login_audits', function (Blueprint $table) {
            // Modificar campos existentes
            $table->string('user_agent')->change(); // Remover límite de 255
            $table->timestamp('created_at')->useCurrent()->change();
            
            // Agregar nuevos campos
            $table->string('email_attempted')->nullable()->after('user_id');
            $table->enum('login_method', ['email_password', 'social_google', 'social_facebook', 'social_apple'])->default('email_password')->after('user_agent');
            $table->enum('failure_reason', ['invalid_credentials', 'account_suspended', 'email_not_verified', 'too_many_attempts', 'other'])->nullable()->after('success');
            $table->string('session_id')->nullable()->after('failure_reason');
            $table->string('device_fingerprint')->nullable()->after('session_id');
            $table->string('location_country', 100)->nullable()->after('device_fingerprint');
            $table->string('location_city', 100)->nullable()->after('location_country');
        });

        Schema::table('login_audits', function (Blueprint $table) {
            // Agregar nuevos índices
            $table->index(['ip', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['email_attempted', 'created_at']);
            $table->index(['ip', 'success', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_audits', function (Blueprint $table) {
            $table->dropIndex(['ip', 'created_at']);
            $table->dropIndex(['success', 'created_at']);
            $table->dropIndex(['email_attempted', 'created_at']);
            $table->dropIndex(['ip', 'success', 'created_at']);
            
            $table->dropColumn([
                'email_attempted', 'login_method', 'failure_reason', 
                'session_id', 'device_fingerprint', 'location_country', 'location_city'
            ]);
            
            // Revertir cambios en campos existentes
            $table->string('user_agent', 255)->change();
        });
    }
};
