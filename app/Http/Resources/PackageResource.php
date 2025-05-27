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
            'price_soles' => $this->price_soles,
            'original_price_soles' => $this->original_price_soles,
            'validity_days' => $this->validity_days,
            'package_type' => $this->package_type,
            'billing_type' => $this->billing_type,
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

            // Computed attributes using model accessors
            'is_unlimited' => $this->is_unlimited,
            'is_on_sale' => $this->is_on_sale,
            'discount_percentage' => $this->discount_percentage,
            'features_string' => $this->features_string,
            'restrictions_string' => $this->restrictions_string,
            'price_per_credit' => $this->price_per_credit,
            'type_display_name' => $this->type_display_name,
            'billing_type_display_name' => $this->billing_type_display_name,
            'validity_period' => $this->validity_period,
            'is_active' => $this->status === 'active',

            // Conditional relationships (if loaded)
            // 'user_packages' => $this->whenLoaded('userPackages', function () {
            //     return UserPackageResource::collection($this->userPackages);
            // }),

            // Stats (if loaded)
            'stats' => $this->when(isset($this->user_packages_count), [
                'total_user_packages' => $this->user_packages_count ?? 0,
                'active_user_packages' => $this->active_user_packages_count ?? 0,
            ]),
        ];
    }
}
