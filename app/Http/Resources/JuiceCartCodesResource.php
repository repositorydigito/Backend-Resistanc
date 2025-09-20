<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JuiceCartCodesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener las bebidas del carrito con sus relaciones
        $drinks = $this->drinks()->with(['basesdrinks', 'flavordrinks', 'typesdrinks'])->get();

        // Formatear las bebidas del carrito
        $formattedDrinks = $drinks->map(function ($drink) {
            $baseDrink = $drink->basesdrinks->first();
            $flavorDrink = $drink->flavordrinks->first();
            $typeDrink = $drink->typesdrinks->first();
            $pivot = $drink->pivot;

            // Verificar si algún ingrediente está inactivo
            $isAvailable = true;
            $inactiveIngredients = [];

            if ($baseDrink && !$baseDrink->is_active) {
                $isAvailable = false;
                $inactiveIngredients[] = 'Base: ' . $baseDrink->name;
            }

            if ($flavorDrink && !$flavorDrink->is_active) {
                $isAvailable = false;
                $inactiveIngredients[] = 'Sabor: ' . $flavorDrink->name;
            }

            if ($typeDrink && !$typeDrink->is_active) {
                $isAvailable = false;
                $inactiveIngredients[] = 'Tipo: ' . $typeDrink->name;
            }

            $drinkData = [
                'id' => $drink->id,
                'quantity' => $pivot->quantity,
                'is_available' => $isAvailable,
            ];

            // Solo agregar base si existe
            if ($baseDrink) {
                $drinkData['base'] = [
                    'id' => $baseDrink->id,
                    'name' => $baseDrink->name,
                    'image_url' => $baseDrink->image_url ? asset( 'storage/' . $baseDrink->image_url) : null,
                    'ico_url' => $baseDrink->ico_url ?  asset( 'storage/' . $baseDrink->ico_url) : null,
                    'is_active' => $baseDrink->is_active
                ];
            }

            // Solo agregar flavor si existe
            if ($flavorDrink) {
                $drinkData['flavor'] = [
                    'id' => $flavorDrink->id,
                    'name' => $flavorDrink->name,
                    'image_url' => $flavorDrink->image_url ?  asset( 'storage/' . $flavorDrink->image_url) : null,
                    'ico_url' => $flavorDrink->ico_url ?  asset( 'storage/' . $flavorDrink->ico_url) : null,
                    'is_active' => $flavorDrink->is_active
                ];
            }

            // Solo agregar type si existe
            if ($typeDrink) {
                $drinkData['type'] = [
                    'id' => $typeDrink->id,
                    'name' => $typeDrink->name,
                    'price' => $typeDrink->price,
                    'image_url' => $typeDrink->image_url ?  asset( 'storage/' . $typeDrink->image_url) : null,
                    'ico_url' => $typeDrink->ico_url ?  asset( 'storage/' . $typeDrink->ico_url) : null,
                    'is_active' => $typeDrink->is_active
                ];

                // Calcular precio total solo si hay tipo con precio y está disponible
                if ($isAvailable) {
                    $drinkData['unit_price'] = $typeDrink->price;
                    $drinkData['total_price'] = $typeDrink->price * $pivot->quantity;
                } else {
                    $drinkData['total_price'] = 0;
                }
            } else {
                $drinkData['total_price'] = 0;
            }

            // Agregar información sobre ingredientes inactivos si los hay
            if (!$isAvailable) {
                $drinkData['inactive_ingredients'] = $inactiveIngredients;
                $drinkData['unavailable_reason'] = 'Uno o más ingredientes no están disponibles';
            }

            // Solo agregar descripción si no es null
            if ($drink->description) {
                $drinkData['description'] = $drink->description;
            }

            return $drinkData;
        });

        // Calcular totales considerando solo bebidas disponibles
        $availableDrinks = $formattedDrinks->where('is_available', true);
        $unavailableDrinks = $formattedDrinks->where('is_available', false);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'user_id' => $this->user_id,
            'is_used' => $this->is_used,
            'juice_order_id' => $this->juice_order_id,
            'total_items' => $formattedDrinks->count(),
            'available_items' => $availableDrinks->count(),
            // 'unavailable_items' => $unavailableDrinks->count(),
            'total_price' => $availableDrinks->sum('total_price'),
            'drinks' => $formattedDrinks,
            // 'summary' => [
            //     'can_checkout' => $unavailableDrinks->isEmpty(),
            //     'warning_message' => $unavailableDrinks->isNotEmpty()
            //         ? 'Algunas bebidas no están disponibles para la compra'
            //         : null
            // ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
