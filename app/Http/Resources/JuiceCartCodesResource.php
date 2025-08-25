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
            $typeDrink = $drink->typesdrinks->first();
            $pivot = $drink->pivot;

            $drinkData = [
                'id' => $drink->id,
                'base' => [
                    'id' => $drink->basesdrinks->first()->id,
                    'name' => $drink->basesdrinks->first()->name
                ],
                'flavor' => [
                    'id' => $drink->flavordrinks->first()->id,
                    'name' => $drink->flavordrinks->first()->name
                ],
                'type' => [
                    'id' => $typeDrink->id,
                    'name' => $typeDrink->name,
                    'price' => $typeDrink->price
                ],
                'quantity' => $pivot->quantity,
                'total_price' => $typeDrink->price * $pivot->quantity
            ];

            // Solo agregar descripción si no es null
            if ($drink->description) {
                $drinkData['description'] = $drink->description;
            }

            return $drinkData;
        });

        return [
            'id' => $this->id,
            'code' => $this->code,
            'user_id' => $this->user_id,
            'is_used' => $this->is_used,
            'juice_order_id' => $this->juice_order_id,
            'total_items' => $formattedDrinks->count(),
            'total_price' => $formattedDrinks->sum('total_price'),
            'drinks' => $formattedDrinks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
