<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'classes_quantity' => $this->classes_quantity,
            'price_soles' => number_format($this->price_soles, 2, '.', ''), // Formato: 999.99
            'original_price_soles' => number_format($this->original_price_soles, 2, '.', ''),

            'package_type' => $this->package_type,
            'billing_type' => $this->billing_type,
            'duration_in_months' => $this->duration_in_months, // New field for duration in months
            'is_virtual_access' => $this->is_virtual_access,
            'priority_booking_days' => $this->priority_booking_days,
            'auto_renewal' => $this->auto_renewal,
            'is_featured' => $this->is_featured,
            'is_popular' => $this->is_popular,
            'status' => $this->status,
            'display_order' => $this->display_order,
            'features' => $this->features,
            'restrictions' => $this->restrictions,
            'target_audience' => $this->target_audience,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'color_hex' => $this->color_hex,
            'commercial_type' => $this->commercial_type,
            'icon_url' =>  $this->icon_url ?  asset('storage/' . $this->icon_url)  : asset('default/icon.png'), // Ensure the URL is absolute

            'is_membresia' => $this->is_membresia,
            // Computed attributes using model accessors
            'is_unlimited' => $this->is_unlimited,
            'is_on_sale' => $this->is_on_sale,
            'discount_percentage' => $this->discount_percentage,
            'features_string' => $this->features_string,
            'restrictions_string' => $this->restrictions_string,
            'price_per_credit' => round($this->price_per_credit, 2),
            'type_display_name' => $this->type_display_name,
            'billing_type_display_name' => $this->billing_type_display_name,
            'validity_period' => $this->validity_period,
            'is_active' => $this->status === 'active',

            // Conditional relationships (if loaded)
            // TODO: Add user packages relationship when needed


            // ✅ AGREGAR INFORMACIÓN DE DISCIPLINAS
            'disciplines' => $this->whenLoaded('disciplines', function () {
                return $this->disciplines->map(function ($discipline) {
                    return [
                        'id' => $discipline->id,
                        'name' => $discipline->name,
                        'display_name' => $discipline->display_name,
                        'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : asset('default/icon.png'),
                        'color_hex' => $discipline->color_hex,
                        'is_active' => $discipline->is_active,
                        'order' => $discipline->order,
                    ];
                });
            }),

            // ✅ AGREGAR INFORMACIÓN DE MEMBRESÍA
            'membership' => $this->whenLoaded('membership', function () {
                return [
                    'id' => $this->membership->id,
                    'name' => $this->membership->name,
                    'slug' => $this->membership->slug,
                    'level' => $this->membership->level,
                    'color_hex' => $this->membership->color_hex,
                    'colors' => $this->membership->colors,
                    'benefits' => $this->membership->benefits,
                    'is_active' => $this->membership->is_active,
                    'display_order' => $this->membership->display_order,
                    'classes_before' => $this->membership->classes_before ?? 0,
                    'is_benefit_shake' => $this->membership->is_benefit_shake ? true : false,
                    'shake_quantity' => $this->membership->shake_quantity ?? 0,
                    'is_benefit_discipline' => $this->membership->is_benefit_discipline ? true : false,
                    'discipline' => [
                        'id' => $this->membership->discipline->id ?? null,
                        'name' => $this->membership->discipline->name ?? null,
                        'icon_url' => $this->membership->discipline && $this->membership->discipline->icon_url
                            ? asset('storage/' . $this->membership->discipline->icon_url)
                            : asset('default/icon.png'),
                        'quantity' => $this->membership->discipline_quantity ?? 0
                    ]
                ];
            }),

            // Stats (if loaded)
            'stats' => $this->when(isset($this->user_packages_count), [
                'total_user_packages' => $this->user_packages_count ?? 0,
                'active_user_packages' => $this->active_user_packages_count ?? 0,
            ]),
        ];
    }
}
