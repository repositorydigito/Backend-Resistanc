<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductOptionType;
use App\Models\VariantOption;

class GenerateProductVariantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-variants 
                            {product_id? : ID del producto específico}
                            {--all : Generar para todos los productos que requieren variantes}
                            {--dry-run : Mostrar qué se generaría sin crear nada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera todas las combinaciones posibles de variantes para productos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productId = $this->argument('product_id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 MODO SIMULACIÓN - No se crearán variantes');
        }

        // Obtener productos
        if ($productId) {
            $products = Product::where('id', $productId)->get();
        } elseif ($all) {
            $products = Product::where('requires_variants', true)->get();
        } else {
            $this->error('Debes especificar un product_id o usar --all');
            return 1;
        }

        if ($products->isEmpty()) {
            $this->error('No se encontraron productos');
            return 1;
        }

        $this->info("Procesando {$products->count()} producto(s)...");

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($products as $product) {
            $this->info("\n📦 Producto: {$product->name} (ID: {$product->id})");
            
            $result = $this->generateVariantsForProduct($product, $dryRun);
            $totalCreated += $result['created'];
            $totalSkipped += $result['skipped'];
        }

        $this->info("\n✅ Resumen:");
        $this->info("   - Variantes creadas: {$totalCreated}");
        $this->info("   - Variantes existentes (omitidas): {$totalSkipped}");

        if ($dryRun) {
            $this->info("   - Ejecuta sin --dry-run para crear las variantes");
        }

        return 0;
    }

    private function generateVariantsForProduct(Product $product, bool $dryRun = false): array
    {
        // Obtener tipos de opciones activos
        $optionTypes = ProductOptionType::where('is_active', true)->get();
        
        if ($optionTypes->isEmpty()) {
            $this->warn("   ⚠️  No hay tipos de opciones disponibles");
            return ['created' => 0, 'skipped' => 0];
        }

        // Obtener opciones por tipo
        $optionsByType = [];
        foreach ($optionTypes as $optionType) {
            $options = VariantOption::where('product_option_type_id', $optionType->id)->get();
            if ($options->isNotEmpty()) {
                $optionsByType[$optionType->id] = $options;
                $this->info("   📋 {$optionType->name}: " . $options->pluck('name')->implode(', '));
            }
        }

        if (empty($optionsByType)) {
            $this->warn("   ⚠️  No hay opciones disponibles");
            return ['created' => 0, 'skipped' => 0];
        }

        // Generar combinaciones
        $combinations = $this->generateCombinationsRecursive($optionsByType);
        $this->info("   🔄 Generando {$combinations->count()} combinaciones posibles...");

        $created = 0;
        $skipped = 0;
        $basePrice = $product->price_soles ?? 0;

        foreach ($combinations as $combination) {
            // Verificar si ya existe
            $existingVariant = $product->variants()->whereHas('variantOptions', function ($query) use ($combination) {
                $query->whereIn('variant_option_id', $combination);
            }, '=', count($combination))->first();

            if ($existingVariant) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                // Crear variante
                $variant = $product->variants()->create([
                    'sku' => $this->generateSku($product, $combination),
                    'price_soles' => $basePrice,
                    'stock_quantity' => 0,
                    'is_active' => true,
                ]);
                
                // Sincronizar las opciones de variante después de crear
                $variant->variantOptions()->sync($combination);
            }

            $created++;
            
            // Mostrar progreso
            $combinationNames = collect($combination)->map(function ($optionId) {
                $option = VariantOption::find($optionId);
                return $option ? $option->name : 'N/A';
            })->implode(' + ');
            
            $this->line("   ➕ " . ($dryRun ? '[SIMULACIÓN] ' : '') . $combinationNames);
        }

        $this->info("   ✅ Creadas: {$created}, Omitidas: {$skipped}");

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function generateCombinationsRecursive($optionsByType, $currentCombination = [], $typeIndex = 0)
    {
        $typeIds = array_keys($optionsByType);
        
        if ($typeIndex >= count($typeIds)) {
            return collect([array_values($currentCombination)]);
        }
        
        $currentTypeId = $typeIds[$typeIndex];
        $options = $optionsByType[$currentTypeId];
        
        $combinations = collect();
        
        foreach ($options as $option) {
            $newCombination = $currentCombination;
            $newCombination[$currentTypeId] = $option->id;
            
            $subCombinations = $this->generateCombinationsRecursive($optionsByType, $newCombination, $typeIndex + 1);
            $combinations = $combinations->merge($subCombinations);
        }
        
        return $combinations;
    }

    private function generateSku(Product $product, array $combination): string
    {
        $baseSku = $product->sku ?? 'PROD';
        $combinationString = '';
        
        foreach ($combination as $optionId) {
            $option = VariantOption::find($optionId);
            if ($option) {
                $combinationString .= '-' . strtoupper(substr($option->name, 0, 3));
            }
        }
        
        return $baseSku . $combinationString;
    }
} 