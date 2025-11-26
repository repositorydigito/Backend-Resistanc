<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\User;
use App\Models\StripeTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class TransactionsRelationManager extends RelationManager
{
    // No usamos una relación real, obtenemos datos de Stripe
    protected static string $relationship = 'userTransactions';

    protected static ?string $title = 'Transacciones y Pagos (Stripe)';

    protected static ?string $modelLabel = 'Transacción';

    protected static ?string $pluralModelLabel = 'Transacciones';

    public function form(Form $form): Form
    {
        // No permitir crear/editar desde aquí, las transacciones se manejan en Stripe
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Retornar una consulta vacía ya que usaremos datos de Stripe
                return $query->whereRaw('1 = 0');
            })
            ->columns([
                Tables\Columns\TextColumn::make('stripe_id')
                    ->label('ID Stripe')
                    ->searchable(false)
                    ->sortable(false)
                    ->copyable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(function($state) {
                        if ($state === 'charge') return 'success';
                        if ($state === 'payment_intent') return 'info';
                        if ($state === 'invoice') return 'primary';
                        if ($state === 'refund') return 'warning';
                        return 'gray';
                    })
                    ->formatStateUsing(function($state) {
                        if ($state === 'charge') return 'Cargo';
                        if ($state === 'payment_intent') return 'Intención de Pago';
                        if ($state === 'invoice') return 'Factura';
                        if ($state === 'refund') return 'Reembolso';
                        return ucfirst($state ?? 'Desconocido');
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(function($state) {
                        if ($state === 'succeeded' || $state === 'paid') return 'success';
                        if ($state === 'pending') return 'warning';
                        if ($state === 'processing') return 'info';
                        if ($state === 'failed' || $state === 'canceled') return 'danger';
                        if ($state === 'refunded' || $state === 'partially_refunded') return 'warning';
                        return 'gray';
                    })
                    ->formatStateUsing(function($state) {
                        if ($state === 'succeeded') return 'Exitoso';
                        if ($state === 'paid') return 'Pagado';
                        if ($state === 'pending') return 'Pendiente';
                        if ($state === 'processing') return 'Procesando';
                        if ($state === 'failed') return 'Fallido';
                        if ($state === 'canceled') return 'Cancelado';
                        if ($state === 'refunded') return 'Reembolsado';
                        if ($state === 'partially_refunded') return 'Parcialmente Reembolsado';
                        return ucfirst($state ?? 'Desconocido');
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('usd', divideBy: 100)
                    ->sortable(false)
                    ->color(function($record) {
                        return $record && $record->type === 'refund' ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge()
                    ->formatStateUsing(fn($state) => strtoupper($state ?? 'USD')),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(function($record) {
                        return $record && isset($record->description) ? $record->description : null;
                    })
                    ->searchable(false),

                Tables\Columns\TextColumn::make('payment_method_type')
                    ->label('Método de Pago')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '-'),

                Tables\Columns\TextColumn::make('invoice_id')
                    ->label('ID Factura')
                    ->placeholder('-')
                    ->copyable()
                    ->visible(function($record) {
                        return $record && isset($record->invoice_id) && $record->invoice_id !== null;
                    }),

                Tables\Columns\TextColumn::make('subscription_id')
                    ->label('ID Suscripción')
                    ->placeholder('-')
                    ->copyable()
                    ->visible(function($record) {
                        return $record && isset($record->subscription_id) && $record->subscription_id !== null;
                    }),

                Tables\Columns\TextColumn::make('created_at_stripe')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(false),

                Tables\Columns\IconColumn::make('receipt_url')
                    ->label('Recibo')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->url(function($record) {
                        return $record && isset($record->receipt_url) ? $record->receipt_url : null;
                    }, shouldOpenInNewTab: true)
                    ->visible(function($record) {
                        return $record && isset($record->receipt_url) && $record->receipt_url !== null;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'charge' => 'Cargo',
                        'payment_intent' => 'Intención de Pago',
                        'invoice' => 'Factura',
                        'refund' => 'Reembolso',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'succeeded' => 'Exitoso',
                        'paid' => 'Pagado',
                        'pending' => 'Pendiente',
                        'processing' => 'Procesando',
                        'failed' => 'Fallido',
                        'canceled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                    ]),
            ])
            ->defaultSort('created_at_stripe', 'desc')
            ->headerActions([
                // No permitir crear desde aquí, se hace desde Stripe
            ])
            ->actions([
                // No permitir editar/eliminar desde aquí, se hace desde Stripe
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No permitir acciones masivas
                ]),
            ])
            ->emptyStateHeading('No hay transacciones')
            ->emptyStateDescription('Este usuario no tiene transacciones registradas en Stripe.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    /**
     * Sobre escribimos getTableRecords para inyectar datos de Stripe
     */
    public function getTableRecords(): \Illuminate\Contracts\Pagination\Paginator
    {
        $user = $this->getOwnerRecord()->user;
        $stripeTransactions = $this->getTransactionsFromStripe($user);

        // Crear modelos Eloquent basados en los datos de Stripe
        $records = collect();
        foreach ($stripeTransactions as $index => $transactionData) {
            $model = new StripeTransaction();
            // Usar fill para establecer los atributos, asegurando que todos existan
            $model->fill([
                'id' => $index + 1, // ID numérico para Filament
                'stripe_id' => $transactionData['id'] ?? null,
                'type' => $transactionData['type'] ?? null,
                'status' => $transactionData['status'] ?? null,
                'amount' => $transactionData['amount'] ?? 0,
                'currency' => $transactionData['currency'] ?? 'USD',
                'description' => $transactionData['description'] ?? null,
                'payment_method' => $transactionData['payment_method'] ?? null,
                'payment_method_type' => $transactionData['payment_method_type'] ?? null,
                'invoice_id' => $transactionData['invoice_id'] ?? null,
                'subscription_id' => $transactionData['subscription_id'] ?? null,
                'charge_id' => $transactionData['charge_id'] ?? null,
                'created_at_stripe' => $transactionData['created_at_stripe'] ?? null,
                'metadata' => $transactionData['metadata'] ?? [],
                'receipt_url' => $transactionData['receipt_url'] ?? null,
            ]);
            // Establecer atributos directamente para asegurar que estén disponibles
            $model->setAttribute('invoice_id', $transactionData['invoice_id'] ?? null);
            $model->setAttribute('subscription_id', $transactionData['subscription_id'] ?? null);
            $model->setAttribute('receipt_url', $transactionData['receipt_url'] ?? null);
            // Marcar como existente para que Filament lo trate como modelo válido
            $model->syncOriginal();
            $records->push($model);
        }

        // Paginar manualmente
        $page = request()->get('page', 1);
        $perPage = $this->tableRecordsPerPage ?? 15;
        $offset = ($page - 1) * $perPage;
        $items = $records->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $records->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Obtiene las transacciones/pagos directamente desde Stripe
     */
    private function getTransactionsFromStripe(?User $user): Collection
    {
        if (!$user || !$user->stripe_id) {
            return collect([]);
        }

        try {
            $stripe = $this->makeStripeClient();

            $transactions = [];
            $seenIds = []; // Para evitar duplicados

            // 1. Obtener cargos (charges)
            try {
                $charges = $stripe->charges->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100,
                ]);

                foreach ($charges->data as $charge) {
                    $transaction = $this->transformCharge($charge);
                    if (!in_array($transaction['stripe_id'], $seenIds)) {
                        $transactions[] = $transaction;
                        $seenIds[] = $transaction['stripe_id'];
                        if ($transaction['charge_id']) {
                            $seenIds[] = $transaction['charge_id'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener cargos de Stripe', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 2. Obtener intenciones de pago (payment intents)
            try {
                $paymentIntents = $stripe->paymentIntents->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100,
                ]);

                foreach ($paymentIntents->data as $paymentIntent) {
                    // Solo agregar si no hemos visto este ID o su charge asociado
                    $chargeId = $paymentIntent->latest_charge ?? null;
                    if (!in_array($paymentIntent->id, $seenIds) &&
                        (!$chargeId || !in_array($chargeId, $seenIds))) {
                        $transaction = $this->transformPaymentIntent($paymentIntent);
                        $transactions[] = $transaction;
                        $seenIds[] = $transaction['stripe_id'];
                        if ($transaction['charge_id']) {
                            $seenIds[] = $transaction['charge_id'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener intenciones de pago de Stripe', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 3. Obtener facturas (invoices) - estas pueden tener pagos asociados
            try {
                $invoices = $stripe->invoices->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100,
                ]);

                foreach ($invoices->data as $invoice) {
                    // Solo agregar facturas pagadas o con intentos de pago
                    // Y solo si no hemos visto el charge asociado
                    if (($invoice->status === 'paid' || $invoice->charge) &&
                        (!$invoice->charge || !in_array($invoice->charge, $seenIds))) {
                        $transaction = $this->transformInvoice($invoice);
                        // Verificar que no sea duplicado por ID de factura
                        if (!in_array($transaction['stripe_id'], $seenIds)) {
                            $transactions[] = $transaction;
                            $seenIds[] = $transaction['stripe_id'];
                            if ($transaction['charge_id']) {
                                $seenIds[] = $transaction['charge_id'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al obtener facturas de Stripe', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Ordenar por fecha descendente (más recientes primero)
            usort($transactions, function ($a, $b) {
                $dateA = $a['created_at_stripe'] ? strtotime($a['created_at_stripe']) : 0;
                $dateB = $b['created_at_stripe'] ? strtotime($b['created_at_stripe']) : 0;
                return $dateB <=> $dateA;
            });

            return collect($transactions);

        } catch (ApiErrorException $e) {
            Log::error('Error al obtener transacciones desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'stripe_id' => $user->stripe_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        } catch (\Exception $e) {
            Log::error('Error inesperado al obtener transacciones desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Transforma un cargo de Stripe al formato esperado
     */
    private function transformCharge($charge): array
    {
        return [
            'id' => $charge->id,
            'type' => 'charge',
            'status' => $charge->status === 'succeeded' ? 'succeeded' : ($charge->status ?? 'failed'),
            'amount' => $charge->amount ?? 0,
            'currency' => strtolower($charge->currency ?? 'usd'),
            'description' => $charge->description ?? ($charge->statement_descriptor ?? 'Cargo'),
            'payment_method' => $charge->payment_method ?? null,
            'payment_method_type' => $charge->payment_method_details->type ?? null,
            'invoice_id' => $charge->invoice ?? null,
            'subscription_id' => null, // Los cargos pueden tener subscription_id en metadata
            'charge_id' => $charge->id,
            'created_at_stripe' => $charge->created
                ? \Carbon\Carbon::createFromTimestamp($charge->created)
                : null,
            'metadata' => $charge->metadata->toArray() ?? [],
            'receipt_url' => $charge->receipt_url ?? null,
        ];
    }

    /**
     * Transforma una intención de pago de Stripe al formato esperado
     */
    private function transformPaymentIntent($paymentIntent): array
    {
        return [
            'id' => $paymentIntent->id,
            'type' => 'payment_intent',
            'status' => $paymentIntent->status ?? 'pending',
            'amount' => $paymentIntent->amount ?? 0,
            'currency' => strtolower($paymentIntent->currency ?? 'usd'),
            'description' => $paymentIntent->description ?? 'Intención de Pago',
            'payment_method' => $paymentIntent->payment_method ?? null,
            'payment_method_type' => $paymentIntent->payment_method_types[0] ?? null,
            'invoice_id' => $paymentIntent->invoice ?? null,
            'subscription_id' => null,
            'charge_id' => $paymentIntent->latest_charge ?? null,
            'created_at_stripe' => $paymentIntent->created
                ? \Carbon\Carbon::createFromTimestamp($paymentIntent->created)
                : null,
            'metadata' => $paymentIntent->metadata->toArray() ?? [],
            'receipt_url' => null,
        ];
    }

    /**
     * Transforma una factura de Stripe al formato esperado
     */
    private function transformInvoice($invoice): array
    {
        return [
            'id' => $invoice->id,
            'type' => 'invoice',
            'status' => $invoice->status === 'paid' ? 'paid' : ($invoice->status ?? 'pending'),
            'amount' => $invoice->amount_paid ?? $invoice->amount_due ?? 0,
            'currency' => strtolower($invoice->currency ?? 'usd'),
            'description' => $invoice->description ?? ($invoice->lines->data[0]->description ?? 'Factura'),
            'payment_method' => null,
            'payment_method_type' => null,
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription ?? null,
            'charge_id' => $invoice->charge ?? null,
            'created_at_stripe' => $invoice->created
                ? \Carbon\Carbon::createFromTimestamp($invoice->created)
                : null,
            'metadata' => $invoice->metadata->toArray() ?? [],
            'receipt_url' => $invoice->hosted_invoice_url ?? null,
        ];
    }

    /**
     * Crea un cliente de Stripe
     */
    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }
}

