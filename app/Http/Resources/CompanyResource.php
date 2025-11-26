<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'social_reason' => $this->social_reason,
            'address' => $this->address,
            'phone' => $this->phone,
            'phone_whassap' => $this->phone_whassap,
            'phone_help' => $this->phone_help,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'signature_image' => $this->signature_image,
            'social_networks' => $this->social_networks,
            // 'is_production' => $this->is_production,

            // Solo mostrar datos sensibles si el usuario tiene permisos
            // 'sol_user_production' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->sol_user_production),
            // 'cert_path_production' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->cert_path_production),
            // 'client_id_production' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->client_id_production),

            // 'sol_user_evidence' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->sol_user_evidence),
            // 'cert_path_evidence' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->cert_path_evidence),
            // 'client_id_evidence' => $this->when($request->user() && $request->user()->hasPermission('view-billing-details'), $this->client_id_evidence),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
