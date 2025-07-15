<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductVariant;

class ProductVariantApiController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'price_soles' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'is_active' => 'boolean',
            'variant_option_ids' => 'required|array|min:1',
            'variant_option_ids.*' => 'exists:variant_option,id',
            // Puedes agregar más validaciones según tus campos
        ]);

        $variant = ProductVariant::create([
            'product_id' => $validated['product_id'],
            'sku' => $validated['sku'],
            'price_soles' => $validated['price_soles'],
            'stock_quantity' => $validated['stock_quantity'],
            'is_active' => $validated['is_active'] ?? true,
            // Agrega aquí otros campos si los necesitas
        ]);

        $variant->variantOptions()->sync($validated['variant_option_ids']);

        return response()->json([
            'success' => true,
            'variant' => $variant->load('variantOptions'),
        ]);
    }
} 