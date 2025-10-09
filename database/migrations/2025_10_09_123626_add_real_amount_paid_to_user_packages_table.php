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
        Schema::table('user_packages', function (Blueprint $table) {
            $table->decimal('real_amount_paid_soles', 10, 2)->nullable()->after('amount_paid_soles')->comment('Monto real pagado (después de descuentos)');
            $table->decimal('original_package_price_soles', 10, 2)->nullable()->after('real_amount_paid_soles')->comment('Precio original del paquete');
            $table->string('promo_code_used')->nullable()->after('original_package_price_soles')->comment('Código promocional usado');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('promo_code_used')->comment('Porcentaje de descuento aplicado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn([
                'real_amount_paid_soles',
                'original_package_price_soles',
                'promo_code_used',
                'discount_percentage'
            ]);
        });
    }
};
