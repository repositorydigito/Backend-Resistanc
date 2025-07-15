<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductOptionType;
use App\Models\VariantOption;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductVariantsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $productsWithVariants = Product::where('requires_variants', true)->count();
        $totalVariants = ProductVariant::count();
        $activeVariants = ProductVariant::where('is_active', true)->count();
        $totalOptionTypes = ProductOptionType::where('is_active', true)->count();
        $totalOptions = VariantOption::count();

        // Calcular variantes sin stock
        $variantsWithoutStock = ProductVariant::where('stock_quantity', 0)->count();
        
        // Calcular variantes con stock bajo
        $variantsLowStock = ProductVariant::whereRaw('stock_quantity <= min_stock_alert')
            ->where('stock_quantity', '>', 0)
            ->count();

        return [
            Stat::make('Productos Totales', $totalProducts)
                ->description('Todos los productos en el sistema')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('gray'),

            Stat::make('Productos con Variantes', $productsWithVariants)
                ->description('Productos que requieren variantes')
                ->descriptionIcon('heroicon-m-tag')
                ->color('blue'),

            Stat::make('Variantes Totales', $totalVariants)
                ->description('Todas las variantes creadas')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('green'),

            Stat::make('Variantes Activas', $activeVariants)
                ->description('Variantes disponibles para venta')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Tipos de Opciones', $totalOptionTypes)
                ->description('Tipos de variantes (Talla, Color, etc.)')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('purple'),

            Stat::make('Opciones Disponibles', $totalOptions)
                ->description('Opciones de variante (S, M, L, Rojo, etc.)')
                ->descriptionIcon('heroicon-m-ellipsis-horizontal')
                ->color('orange'),

            Stat::make('Sin Stock', $variantsWithoutStock)
                ->description('Variantes agotadas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Stock Bajo', $variantsLowStock)
                ->description('Variantes con stock bajo')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];
    }
} 