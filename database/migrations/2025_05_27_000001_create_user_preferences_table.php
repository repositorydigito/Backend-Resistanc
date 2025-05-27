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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Preferencias de clases y entrenamiento
            $table->json('preferred_class_times')->nullable()->comment('Horarios preferidos por día de la semana');
            $table->json('preferred_disciplines')->nullable()->comment('Array de discipline IDs preferidas');
            $table->json('preferred_instructors')->nullable()->comment('Array de instructor IDs preferidos');
            
            // Configuración de notificaciones
            $table->json('notification_preferences')->nullable()->comment('Configuración de notificaciones');
            
            // Preferencias personales
            $table->json('dietary_restrictions')->nullable();
            $table->json('fitness_goals')->nullable();
            $table->json('music_preferences')->nullable();
            $table->json('equipment_preferences')->nullable();
            
            // Configuración de idioma y zona horaria
            $table->enum('communication_language', ['es', 'en'])->default('es');
            $table->string('timezone', 50)->default('America/Lima');
            
            // Configuración de privacidad
            $table->json('privacy_settings')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('data_processing_consent')->default(true);
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Índices
            $table->unique('user_id');
            $table->index('communication_language');
            $table->index(['marketing_consent', 'data_processing_consent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
