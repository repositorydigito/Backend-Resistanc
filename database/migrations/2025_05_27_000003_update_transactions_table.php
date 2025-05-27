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
        Schema::table('transactions', function (Blueprint $table) {
            // Agregar subscription_id solo si no existe
            if (!Schema::hasColumn('transactions', 'subscription_id')) {
                $table->foreignId('subscription_id')->nullable()
                      ->after('user_package_id')
                      ->constrained('subscriptions')->onDelete('set null');
            }

            // Eliminar columnas que no coinciden (verificar que existan)
            $columnsToRemove = [];
            if (Schema::hasColumn('transactions', 'transaction_code')) {
                $columnsToRemove[] = 'transaction_code';
            }
            if (Schema::hasColumn('transactions', 'payment_method')) {
                $columnsToRemove[] = 'payment_method';
            }
            if (Schema::hasColumn('transactions', 'payment_provider')) {
                $columnsToRemove[] = 'payment_provider';
            }
            if (Schema::hasColumn('transactions', 'external_transaction_id')) {
                $columnsToRemove[] = 'external_transaction_id';
            }
            if (Schema::hasColumn('transactions', 'authorization_code')) {
                $columnsToRemove[] = 'authorization_code';
            }
            if (Schema::hasColumn('transactions', 'processing_fee')) {
                $columnsToRemove[] = 'processing_fee';
            }
            if (Schema::hasColumn('transactions', 'payment_details')) {
                $columnsToRemove[] = 'payment_details';
            }
            if (Schema::hasColumn('transactions', 'status')) {
                $columnsToRemove[] = 'status';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Actualizar enum de transaction_type
            $table->enum('transaction_type', [
                'package_purchase', 'product_order', 'subscription_payment',
                'refund', 'chargeback', 'fee'
            ])->change();

            // Agregar campos solo si no existen
            if (!Schema::hasColumn('transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 4)->nullable()->after('currency');
            }

            if (!Schema::hasColumn('transactions', 'gateway_provider')) {
                $table->enum('gateway_provider', [
                    'culqi', 'niubiz', 'paypal', 'stripe', 'izipay', 'payu'
                ])->after('exchange_rate');
            }

            if (!Schema::hasColumn('transactions', 'gateway_transaction_id')) {
                $table->string('gateway_transaction_id')->nullable()->after('gateway_provider');
            }

            if (!Schema::hasColumn('transactions', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('gateway_transaction_id')
                      ->comment('Respuesta completa del gateway');
            }

            if (!Schema::hasColumn('transactions', 'confirmation_code')) {
                $table->string('confirmation_code', 100)->nullable()->after('gateway_response');
            }

            if (!Schema::hasColumn('transactions', 'reference_number')) {
                $table->string('reference_number', 100)->nullable()->after('confirmation_code');
            }

            if (!Schema::hasColumn('transactions', 'payment_status')) {
                $table->enum('payment_status', [
                    'pending', 'processing', 'completed', 'failed',
                    'cancelled', 'refunded', 'disputed'
                ])->default('pending')->after('reference_number');
            }

            if (!Schema::hasColumn('transactions', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('failure_reason');
            }

            if (!Schema::hasColumn('transactions', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('processed_at');
            }

            if (!Schema::hasColumn('transactions', 'fees')) {
                $table->json('fees')->nullable()->after('reconciled_at')
                      ->comment('Comisiones y fees aplicados');
            }

            if (!Schema::hasColumn('transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('fees')
                      ->comment('Metadata adicional del pago');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Actualizar timestamps para usar CURRENT_TIMESTAMP
            $table->timestamp('created_at')->useCurrent()->change();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->change();
        });

        // Agregar índices en una operación separada con manejo de errores
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('gateway_transaction_id', 'idx_transactions_gateway');
            });
        } catch (\Exception) {
            // Índice ya existe, continuar
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('payment_status', 'idx_transactions_status');
            });
        } catch (\Exception) {
            // Índice ya existe, continuar
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('transaction_type', 'idx_transactions_type');
            });
        } catch (\Exception) {
            // Índice ya existe, continuar
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('created_at', 'idx_transactions_date');
            });
        } catch (\Exception) {
            // Índice ya existe, continuar
        }

        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'payment_status'], 'idx_transactions_user');
            });
        } catch (\Exception) {
            // Índice ya existe, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Eliminar índices nuevos
            $table->dropIndex('idx_transactions_gateway');
            $table->dropIndex('idx_transactions_status');
            $table->dropIndex('idx_transactions_type');
            $table->dropIndex('idx_transactions_date');
            $table->dropIndex('idx_transactions_user');

            // Eliminar columnas agregadas
            $table->dropColumn([
                'subscription_id', 'exchange_rate', 'gateway_provider',
                'gateway_transaction_id', 'gateway_response', 'confirmation_code',
                'reference_number', 'payment_status', 'refund_reason',
                'reconciled_at', 'fees', 'metadata'
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Restaurar columnas originales
            $table->string('transaction_code', 30)->unique();
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'cash']);
            $table->string('payment_provider', 50)->nullable()->comment('visa, mastercard, yape, plin, etc.');
            $table->string('external_transaction_id')->nullable()->comment('ID del proveedor de pago');
            $table->string('authorization_code', 50)->nullable();
            $table->decimal('processing_fee', 8, 2)->default(0.00);
            $table->json('payment_details')->nullable()->comment('Detalles específicos del método de pago');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']);

            // Restaurar enum original de transaction_type
            $table->enum('transaction_type', [
                'package_purchase', 'product_purchase', 'subscription_payment',
                'refund', 'partial_refund', 'cancellation_fee'
            ])->change();

            // Restaurar índices originales (solo los que no existen)
            $table->index(['transaction_type', 'status']);
            $table->index('status');
            $table->index('transaction_code');
            $table->index('external_transaction_id');
            $table->index('processed_at');
        });
    }

};
