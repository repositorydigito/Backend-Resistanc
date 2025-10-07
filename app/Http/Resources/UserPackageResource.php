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
            'total_classes' => $this->total_classes,
            'used_classes' => $this->used_classes,
            'remaining_classes' => $this->remaining_classes,
            'amount_paid_soles' => number_format((float) $this->amount_paid_soles, 2, '.', ''),
            'currency' => $this->currency,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'activation_date' => $this->activation_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'status' => $this->status,
            'auto_renew' => $this->auto_renew,
            'renewal_price' => $this->renewal_price ? number_format((float) $this->renewal_price, 2, '.', '') : null,
            'benefits_included' => $this->benefits_included,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed attributes using model accessors
            'status_display_name' => $this->status_display_name,
            'is_expired' => $this->is_expired,
            'is_valid' => $this->is_valid,

            // Package information (if loaded)
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
                                'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : null,
                                'color_hex' => $discipline->color_hex,
                                'order' => $discipline->order,
                            ];
                        })
                        : [],
                ];
            }),
        ];
    }
}
