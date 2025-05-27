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
        Schema::table('social_accounts', function (Blueprint $table) {
            // Agregar nuevos providers
            $table->dropColumn('provider');
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->enum('provider', ['google', 'facebook', 'apple', 'instagram', 'tiktok'])->after('user_id');
            $table->string('provider_name')->nullable()->after('provider_email');
            $table->string('provider_avatar', 500)->nullable()->after('provider_name');
            $table->text('refresh_token')->nullable()->after('token');
            $table->boolean('is_active')->default(true)->after('token_expires_at');
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            // Agregar nuevos índices
            $table->index(['provider', 'is_active']);
            $table->index('provider_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropIndex(['provider', 'is_active']);
            $table->dropIndex(['provider_email']);
            $table->dropColumn(['provider_name', 'provider_avatar', 'refresh_token', 'is_active']);
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn('provider');
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->enum('provider', ['google', 'facebook'])->after('user_id');
        });
    }
};
