<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $isFavorite = false;

        // Verificar si el usuario está autenticado
        if ($request->user()) {
            $isFavorite = $request->user()->favoriteProducts()
                ->where('favoritable_id', (string)$this->id)
                ->where('favoritable_type', Product::class)
                ->exists();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price_soles' => $this->price_soles,
            'cost_price_soles' => $this->cost_price_soles,
            'compare_price_soles' => $this->compare_price_soles,
            'final_price' => $this->final_price,
            'discount_percentage' => $this->discount_percentage,
            'is_on_sale' => $this->is_on_sale,
            'stock_quantity' => $this->stock_quantity,
            'min_stock_alert' => $this->min_stock_alert,
            'is_in_stock' => $this->is_in_stock,
            'is_low_stock' => $this->is_low_stock,
            'is_favorite' => $isFavorite,
            'img_url' => $this->img_url
                ? asset('storage/' . $this->img_url)
                : 'https://www.mytheresa.com/media/1094/1238/100/2c/P00727373.jpg',

            'images' => collect($this->images)->map(fn($path) => url('storage/' . ltrim($path, '/'))),

            'weight_grams' => $this->weight_grams,
            'dimensions' => $this->dimensions,
            'nutritional_info' => $this->nutritional_info,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'product_type' => $this->product_type,
            'requires_variants' => $this->requires_variants,
            'is_virtual' => $this->is_virtual,
            'is_featured' => $this->is_featured,
            'is_available_for_booking' => $this->is_available_for_booking,
            'status' => $this->status,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_cupon' => $this->is_cupon,
            'url_cupon_code' => $this->url_cupon_code,
            'url' => $this->url,


            // Relaciones
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug ?? null,
            ]),
            'product_brand' => $this->whenLoaded('productBrand', fn() => [
                'id' => $this->productBrand->id,
                'name' => $this->productBrand->name,
            ]),

            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            'tags' => $this->whenLoaded('tags', fn() => $this->tags->pluck('name')),

            // Otros atributos útiles
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
