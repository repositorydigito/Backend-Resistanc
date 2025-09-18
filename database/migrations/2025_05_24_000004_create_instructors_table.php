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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Si el instructor tiene cuenta de usuario');
            $table->string('name')->comment('Nombre del instructor');
            $table->string('email')->unique()->comment('Correo electrónico del instructor');
            $table->string('phone', 15)->nullable()->comment('Teléfono del instructor');
            $table->json('specialties')->comment('Array de discipline IDs');
            $table->text('bio')->nullable()->comment('Biografía del instructor');
            $table->json('certifications')->nullable()->comment('Certificaciones y títulos');
            $table->string('profile_image')->nullable()->comment('URL de la imagen de perfil del instructor');
            $table->string('instagram_handle', 100)->nullable()->comment('Instagram del instructor');
            $table->boolean('is_head_coach')->default(false)->comment('Indica si el instructor es el entrenador principal');
            $table->unsignedTinyInteger('experience_years')->nullable()->comment('Años de experiencia del instructor');
            $table->decimal('rating_average', 3, 2)->default(0.00)->comment('Calificación promedio del instructor');
            $table->unsignedInteger('total_classes_taught')->default(0)->comment('Total de clases impartidas por el instructor');
            $table->date('hire_date')->nullable()->comment('Fecha de contratación del instructor');
            $table->decimal('hourly_rate_soles', 8, 2)->nullable()->comment('Tarifa por hora en soles');
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active')->comment('Estado del instructor');
            $table->json('availability_schedule')->nullable()->comment('Horarios disponibles por día');

            $table->enum('type_document', ['dni', 'passport', 'other'])->default('dni');
            $table->string('document_number', 15)->unique();

            // Índices
            $table->index('status');
            $table->index(['is_head_coach', 'status']);
            $table->index('rating_average');
            $table->index('user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
