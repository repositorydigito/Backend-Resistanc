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
        Schema::create('point_user', function (Blueprint $table) {
            $table->id();

            $table->integer('quantity_point');
            $table->date('date_expire');

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('membresia_id')->nullable()->constrained('memberships')->onDelete('set null')->comment('Membresía con la que se ganaron los puntos');
            $table->foreignId('active_membership_id')->nullable()->constrained('memberships')->onDelete('set null')->comment('Membresía activa con la que se está usando el punto actualmente');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_user');
    }
};
