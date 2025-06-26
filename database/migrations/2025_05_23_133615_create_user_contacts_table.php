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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('phone', 15);
            $table->string('address_line', 255);
            $table->string('city', 100)->nullable(false);
            $table->string('state_province', 100)->nullable();
            $table->string('country', 100)->default('Peru');
            $table->string('postal_code', 20)->nullable();
            $table->enum('contact_type', ['home', 'work', 'emergency', 'other'])->default('home');
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_billing_address')->default(false);


            $table->timestamps();

            // Índices
            $table->index('user_id');
            $table->unique('phone');
            $table->index(['user_id', 'is_primary']);
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
