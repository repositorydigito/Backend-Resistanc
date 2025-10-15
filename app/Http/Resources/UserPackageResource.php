<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para Paquetes de Usuario
 *
 * @property \App\Models\UserPackage $resource
 */
final class UserPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'package_id' => $this->package_id,
            'package_code' => $this->package_code,
            'used_classes' => $this->used_classes,
            'remaining_classes' => $this->remaining_classes,
            'amount_paid_soles' => number_format((float) $this->amount_paid_soles, 2, '.', ''),
            'real_amount_paid_soles' => $this->real_amount_paid_soles ? number_format((float) $this->real_amount_paid_soles, 2, '.', '') : null,
            'original_package_price_soles' => $this->original_package_price_soles ? number_format((float) $this->original_package_price_soles, 2, '.', '') : null,
            'promo_code_used' => $this->promo_code_used,
            'discount_percentage' => $this->discount_percentage,
            'currency' => $this->currency,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'activation_date' => $this->activation_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->status,
            'renewal_price' => $this->renewal_price ? number_format((float) $this->renewal_price, 2, '.', '') : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed attributes using model accessors
            'status_display_name' => $this->status_display_name,
            'is_expired' => $this->is_expired,
            'is_valid' => $this->is_valid,
            'days_remaining' => $this->days_remaining,
            'has_classes' => $this->has_classes,

            // Información del paquete (si está cargado)
            'package' => $this->whenLoaded('package', function () {
                return [
                    'id' => $this->package->id,
                    'name' => $this->package->name,
                    'slug' => $this->package->slug,
                    'description' => $this->package->description,
                    'classes_quantity' => $this->package->classes_quantity,
                    'price_soles' => number_format((float) $this->package->price_soles, 2, '.', ''),
                    'validity_days' => $this->package->validity_days,

                    // Agregar información de disciplinas
                    'disciplines' => $this->package->relationLoaded('disciplines')
                        ? $this->package->disciplines->map(function ($discipline) {
                            return [
                                'id' => $discipline->id,
                                'name' => $discipline->name,
                                'display_name' => $discipline->display_name,
                                'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : asset('default/icon.png'),
                                'color_hex' => $discipline->color_hex,
                                'order' => $discipline->order,
                            ];
                        })
                        : [],
                ];
            }),

            // Resumen de clases compradas y uso
            'classes_summary' => [
                'total_classes_purchased' => $this->package ? $this->package->classes_quantity : null,
                'classes_used' => $this->used_classes,
                'classes_remaining' => $this->remaining_classes,
                'usage_percentage' => $this->package && $this->package->classes_quantity > 0
                    ? round(($this->used_classes / $this->package->classes_quantity) * 100, 1)
                    : 0,
                'classes_available' => $this->remaining_classes > 0,
            ],

            // Información de precios y descuentos
            'pricing_summary' => [
                'original_price' => $this->amount_paid_soles ? number_format((float) $this->amount_paid_soles, 2, '.', '') : null,
                'real_amount_paid' => $this->real_amount_paid_soles ? number_format((float) $this->real_amount_paid_soles, 2, '.', '') : null,
                'package_original_price' => $this->original_package_price_soles ? number_format((float) $this->original_package_price_soles, 2, '.', '') : null,
                'discount_applied' => $this->discount_percentage ? (float) $this->discount_percentage : 0,
                'savings' => $this->amount_paid_soles && $this->real_amount_paid_soles
                    ? number_format((float) $this->amount_paid_soles - (float) $this->real_amount_paid_soles, 2, '.', '')
                    : '0.00',
                'promo_code_used' => $this->promo_code_used,
            ],
        ];
    }
}
