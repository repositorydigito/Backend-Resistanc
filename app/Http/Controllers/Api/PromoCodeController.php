<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCodes;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Códigos Promocionales
 */
class PromoCodeController extends Controller
{
    /**
     * Validar código promocional
     *
     * Verifica si un código promocional es válido para un paquete específico
     */
    public function validate(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'code' => 'required|string|max:255',
                'package_id' => 'required|integer|exists:packages,id'
            ]);

            $code = strtoupper(trim($request->code));
            $packageId = $request->integer('package_id');

            // Buscar el código promocional
            $promoCode = PromoCodes::where('code', $code)->first();

            if (!$promoCode) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Código promocional no encontrado',
                    'datoAdicional' => null
                ], 200);
            }

            // Verificar si está activo
            if ($promoCode->status !== 'active') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Código promocional inactivo',
                    'datoAdicional' => null
                ], 200);
            }

            // Verificar fechas de temporada si el tipo es 'season'
            if ($promoCode->type === 'season') {
                $now = now();

                if ($promoCode->start_date && $now->lt($promoCode->start_date)) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Código promocional aún no está vigente',
                        'datoAdicional' => [
                            'start_date' => $promoCode->start_date->toISOString()
                        ]
                    ], 200);
                }

                if ($promoCode->end_date && $now->gt($promoCode->end_date)) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Código promocional expirado',
                        'datoAdicional' => [
                            'end_date' => $promoCode->end_date->toISOString()
                        ]
                    ], 200);
                }
            }

            // Verificar si el paquete tiene descuento disponible
            $packageDiscount = $promoCode->packages()
                ->where('packages.id', $packageId)
                ->where('packages.status', 'active')
                ->wherePivot('quantity', '>', 0)
                ->first();

            if (!$packageDiscount) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Este código promocional no aplica para el paquete seleccionado',
                    'datoAdicional' => null
                ], 200);
            }

            // Calcular descuento
            $discount = $packageDiscount->pivot->discount ?? 0;
            $quantity = $packageDiscount->pivot->quantity ?? 0;
            $originalPrice = $packageDiscount->price_soles ?? 0;
            $discountAmount = ($originalPrice * $discount) / 100;
            $finalPrice = $originalPrice - $discountAmount;

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Código promocional válido',
                'datoAdicional' => [
                    'code' => $promoCode->code,
                    'name' => $promoCode->name,
                    'discount_percentage' => (float) $discount,
                    'discount_amount' => (float) $discountAmount,
                    'original_price' => (float) $originalPrice,
                    'final_price' => (float) $finalPrice,
                    'available_codes' => $quantity
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de validación incorrectos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al validar código promocional', [
                'code' => $request->code ?? null,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al validar código promocional',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Consumir código promocional
     *
     * Aplica un código promocional a la compra de un paquete
     */
    public function consume(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'code' => 'required|string|max:255',
                'package_id' => 'required|integer|exists:packages,id',
                'amount_paid' => 'required|numeric|min:0'
            ]);

            $userId = Auth::id();
            $code = strtoupper(trim($request->code));
            $packageId = $request->integer('package_id');
            $amountPaid = $request->input('amount_paid');

            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($code, $packageId, $amountPaid, $userId) {

                // Buscar el código promocional con bloqueo
                $promoCode = PromoCodes::where('code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$promoCode) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Código promocional no encontrado',
                        'datoAdicional' => null
                    ], 200);
                }

                // Verificar si está activo
                if ($promoCode->status !== 'active') {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Código promocional inactivo',
                        'datoAdicional' => null
                    ], 200);
                }

                // Verificar fechas de temporada si el tipo es 'season'
                if ($promoCode->type === 'season') {
                    $now = now();

                    if ($promoCode->start_date && $now->lt($promoCode->start_date)) {
                        return response()->json([
                            'exito' => false,
                            'codMensaje' => 0,
                            'mensajeUsuario' => 'Código promocional aún no está vigente',
                            'datoAdicional' => [
                                'start_date' => $promoCode->start_date->toISOString()
                            ]
                        ], 200);
                    }

                    if ($promoCode->end_date && $now->gt($promoCode->end_date)) {
                        return response()->json([
                            'exito' => false,
                            'codMensaje' => 0,
                            'mensajeUsuario' => 'Código promocional expirado',
                            'datoAdicional' => [
                                'end_date' => $promoCode->end_date->toISOString()
                            ]
                        ], 200);
                    }
                }

                // Verificar si el paquete tiene descuento disponible
                $packageDiscount = $promoCode->packages()
                    ->where('packages.id', $packageId)
                    ->where('packages.status', 'active')
                    ->first();

                if (!$packageDiscount) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Este código no aplica para el paquete seleccionado',
                        'datoAdicional' => null
                    ], 200);
                }

                $discount = $packageDiscount->pivot->discount ?? 0;
                $quantity = $packageDiscount->pivot->quantity ?? 0;

                if ($quantity <= 0) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No hay códigos promocionales disponibles para este paquete',
                        'datoAdicional' => [
                            'remaining_quantity' => 0
                        ]
                    ], 200);
                }

                // Calcular descuento
                $originalPrice = $packageDiscount->price_soles ?? 0;
                $discountAmount = ($originalPrice * $discount) / 100;
                $finalPrice = $originalPrice - $discountAmount;

                // Verificar que el monto pagado sea correcto (con margen de error de 1 sol)
                if (abs($amountPaid - $finalPrice) > 1) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'El monto pagado no coincide con el precio final',
                        'datoAdicional' => [
                            'expected_amount' => (float) $finalPrice,
                            'received_amount' => (float) $amountPaid,
                            'difference' => abs($amountPaid - $finalPrice)
                        ]
                    ], 200);
                }

                // Verificar si el usuario ya usó este código con este paquete
                $alreadyUsed = $promoCode->users()
                    ->where('user_id', $userId)
                    ->wherePivot('package_id', $packageId)
                    ->exists();

                if ($alreadyUsed) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Ya has utilizado este código promocional para este paquete',
                        'datoAdicional' => null
                    ], 200);
                }

                // Decrementar cantidad disponible
                $promoCode->packages()->updateExistingPivot($packageId, [
                    'quantity' => DB::raw('quantity - 1')
                ]);

                // Registrar uso del código por el usuario
                $promoCode->users()->attach($userId, [
                    'monto' => $amountPaid,
                    'package_id' => $packageId,
                    'discount_applied' => $discount,
                    'original_price' => $originalPrice,
                    'final_price' => $finalPrice,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Actualizar el UserPackage con la información del código promocional
                $this->updateUserPackageWithPromoCode($userId, $packageId, $promoCode->code, $discount, $originalPrice, $finalPrice);

                Log::info('Código promocional consumido exitosamente', [
                    'user_id' => $userId,
                    'promo_code' => $code,
                    'package_id' => $packageId,
                    'discount' => $discount,
                    'amount_paid' => $amountPaid
                ]);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Código promocional aplicado exitosamente',
                    'datoAdicional' => [
                        'promo_code' => [
                            'code' => $promoCode->code,
                            'name' => $promoCode->name
                        ],
                        'package' => [
                            'id' => $packageDiscount->id,
                            'name' => $packageDiscount->name,
                            'classes_quantity' => $packageDiscount->classes_quantity
                        ],
                        'pricing' => [
                            'original_price' => (float) $originalPrice,
                            'discount_percentage' => (float) $discount,
                            'discount_amount' => (float) $discountAmount,
                            'final_price' => (float) $finalPrice,
                            'amount_paid' => (float) $amountPaid,
                            'savings' => (float) $discountAmount
                        ],
                        'remaining_codes' => $quantity - 1
                    ]
                ], 200);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de validación incorrectos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al consumir código promocional', [
                'user_id' => Auth::id(),
                'code' => $request->code ?? null,
                'package_id' => $request->package_id ?? null,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al consumir código promocional',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Historial de códigos promocionales usados
     *
     * Retorna todos los códigos promocionales que el usuario ha utilizado
     */
    public function myHistory(Request $request)
    {
        try {
            $userId = Auth::id();

            // Obtener códigos promocionales usados por el usuario
            $promoCodes = PromoCodes::whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['users' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->with('package:id,name,classes_quantity');
            }])
            ->get()
            ->map(function ($promoCode) use ($userId) {
                $userUsages = $promoCode->users->where('id', $userId);

                return [
                    'promo_code' => [
                        'id' => $promoCode->id,
                        'code' => $promoCode->code,
                        'name' => $promoCode->name,
                        'supplier' => $promoCode->name_supplier
                    ],
                    'usages' => $userUsages->map(function ($usage) {
                        return [
                            'package' => [
                                'id' => $usage->package?->id,
                                'name' => $usage->package?->name,
                                'classes_quantity' => $usage->package?->classes_quantity
                            ],
                            'pricing' => [
                                'original_price' => (float) $usage->pivot->original_price,
                                'discount_applied' => (float) $usage->pivot->discount_applied,
                                'final_price' => (float) $usage->pivot->final_price,
                                'amount_paid' => (float) $usage->pivot->monto,
                                'savings' => (float) ($usage->pivot->original_price - $usage->pivot->final_price)
                            ],
                            'used_at' => $usage->pivot->created_at?->toISOString()
                        ];
                    })->values(),
                    'total_used' => $userUsages->count(),
                    'total_savings' => $userUsages->sum(function ($usage) {
                        return $usage->pivot->original_price - $usage->pivot->final_price;
                    })
                ];
            });

            if ($promoCodes->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No has utilizado códigos promocionales',
                    'datoAdicional' => []
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Historial obtenido exitosamente',
                'datoAdicional' => [
                    'promo_codes' => $promoCodes,
                    'summary' => [
                        'total_codes_used' => $promoCodes->count(),
                        'total_usages' => $promoCodes->sum('total_used'),
                        'total_savings' => $promoCodes->sum('total_savings')
                    ]
                ]
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Error al obtener historial de códigos promocionales', [
                'user_id' => Auth::id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener historial',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Actualizar UserPackage con información del código promocional
     */
    private function updateUserPackageWithPromoCode(int $userId, int $packageId, string $promoCode, float $discount, float $originalPrice, float $finalPrice): void
    {
        try {
            $userPackage = \App\Models\UserPackage::where('user_id', $userId)
                ->where('package_id', $packageId)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($userPackage) {
                $userPackage->update([
                    'promo_code_used' => $promoCode,
                    'discount_percentage' => $discount,
                    'original_package_price_soles' => $originalPrice,
                    'real_amount_paid_soles' => $finalPrice,
                    // Mantener amount_paid_soles como el precio original del paquete
                ]);

                Log::info('UserPackage actualizado con información de código promocional', [
                    'user_package_id' => $userPackage->id,
                    'promo_code' => $promoCode,
                    'discount' => $discount,
                    'original_price' => $originalPrice,
                    'final_price' => $finalPrice
                ]);
            } else {
                Log::warning('No se encontró UserPackage para actualizar con código promocional', [
                    'user_id' => $userId,
                    'package_id' => $packageId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar UserPackage con código promocional', [
                'user_id' => $userId,
                'package_id' => $packageId,
                'promo_code' => $promoCode,
                'error' => $e->getMessage()
            ]);
        }
    }
}
