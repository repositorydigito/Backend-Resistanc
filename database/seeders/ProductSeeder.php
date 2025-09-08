<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Productos');
        // Productos
        $products = [
            [
                'name' => 'Camiseta Deportiva',
                'sku' => '3543534',
                'slug' => 'camiseta-deportiva',
                'description' => 'Camiseta de alta calidad, ideal para entrenamientos intensos',
                'short_description' => 'Camiseta transpirable y cómoda',
                'price_soles' => 49.99,
                'compare_price_soles' => 69.99,

                'stock_quantity' => 100,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Botella de Agua',
                'sku' => '3543535',
                'slug' => 'botella-de-agua',
                'description' => 'Botella de agua reutilizable, perfecta para mantenerte hidratado durante tus entrenamientos',
                'short_description' => 'Botella de agua de acero inoxidable',
                'price_soles' => 29.99,
                'compare_price_soles' => 39.99,

                'stock_quantity' => 200,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Malla Deportiva',
                'sku' => '3543536',
                'slug' => 'malla-deportiva',
                'description' => 'Malla deportiva de alta compresión, ideal para yoga y pilates',
                'short_description' => 'Malla cómoda y elástica',
                'price_soles' => 59.99,
                'compare_price_soles' => 79.99,

                'stock_quantity' => 150,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Zapatillas de Running',
                'sku' => '3543537',
                'slug' => 'zapatillas-de-running',
                'description' => 'Zapatillas ligeras y cómodas, perfectas para correr largas distancias',
                'short_description' => 'Zapatillas con amortiguación avanzada',
                'price_soles' => 129.99,
                'compare_price_soles' => 159.99,

                'stock_quantity' => 80,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),

            ],
            [
                'name' => 'Toalla Deportiva',
                'sku' => '3543538',
                'slug' => 'toalla-deportiva',
                'description' => 'Toalla de microfibra, ligera y de secado rápido, ideal para el gimnasio',
                'short_description' => 'Toalla suave y absorbente',
                'price_soles' => 19.99,
                'compare_price_soles' => 24.99,

                'stock_quantity' => 300,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mancuernas Ajustables',
                'sku' => '3543539',
                'slug' => 'mancuernas-ajustables',
                'description' => 'Mancuernas ajustables de 1 a 10 kg, perfectas para entrenamientos en casa',
                'short_description' => 'Mancuernas versátiles y prácticas',
                'price_soles' => 89.99,
                'compare_price_soles' => 119.99,

                'stock_quantity' => 50,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bandas de Resistencia',
                'sku' => '3543540',
                'slug' => 'bandas-de-resistencia',
                'description' => 'Set de bandas de resistencia de diferentes niveles, ideales para tonificar y fortalecer',
                'short_description' => 'Bandas de resistencia para todo el cuerpo',
                'price_soles' => 39.99,
                'compare_price_soles' => 49.99,

                'stock_quantity' => 120,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gorra Deportiva',
                'sku' => '3543541',
                'slug' => 'gorra-deportiva',
                'description' => 'Gorra ligera y transpirable, ideal para entrenamientos al aire libre',
                'short_description' => 'Gorra con protección UV',
                'price_soles' => 24.99,
                'compare_price_soles' => 34.99,

                'stock_quantity' => 150,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mochila Deportiva',
                'sku' => '3543542',
                'slug' => 'mochila-deportiva',
                'description' => 'Mochila espaciosa y resistente, perfecta para llevar todo lo necesario al gimnasio',
                'short_description' => 'Mochila con múltiples compartimentos',
                'price_soles' => 59.99,
                'compare_price_soles' => 79.99,

                'stock_quantity' => 70,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reloj Deportivo',
                'sku' => '3543543',
                'slug' => 'reloj-deportivo',
                'description' => 'Reloj inteligente con monitor de actividad y frecuencia cardíaca',
                'short_description' => 'Reloj con GPS y seguimiento de salud',
                'price_soles' => 199.99,
                'compare_price_soles' => 249.99,

                'stock_quantity' => 30, // Stock limitado
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Guantes de Entrenamiento',
                'sku' => '3543544',
                'slug' => 'guantes-de-entrenamiento',
                'description' => 'Guantes antideslizantes con acolchado para proteger tus manos durante los levantamientos.',
                'short_description' => 'Guantes cómodos y seguros',
                'price_soles' => 34.99,
                'compare_price_soles' => 44.99,

                'stock_quantity' => 120,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rodillera Deportiva',
                'sku' => '3543545',
                'slug' => 'rodillera-deportiva',
                'description' => 'Rodillera elástica con soporte y compresión para proteger tus articulaciones.',
                'short_description' => 'Soporte para rodillas',
                'price_soles' => 44.99,
                'compare_price_soles' => 59.99,

                'stock_quantity' => 90,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Proteína Whey 1kg',
                'sku' => '3543546',
                'slug' => 'proteina-whey-1kg',
                'description' => 'Suplemento de proteína de suero de leche sabor chocolate para recuperación muscular.',
                'short_description' => 'Proteína sabor chocolate',
                'price_soles' => 139.90,
                'compare_price_soles' => 169.90,

                'stock_quantity' => 60,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cinturón de Pesas',
                'sku' => '3543547',
                'slug' => 'cinturon-de-pesas',
                'description' => 'Cinturón de cuero resistente para entrenamiento de levantamiento de pesas.',
                'short_description' => 'Cinturón de seguridad',
                'price_soles' => 79.99,
                'compare_price_soles' => 99.99,

                'stock_quantity' => 40,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Esterilla de Yoga',
                'sku' => '3543548',
                'slug' => 'esterilla-de-yoga',
                'description' => 'Colchoneta antideslizante para yoga y pilates, material ecológico.',
                'short_description' => 'Esterilla premium 6mm',
                'price_soles' => 69.99,
                'compare_price_soles' => 89.99,

                'stock_quantity' => 100,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Shaker Pro 600ml',
                'sku' => '3543549',
                'slug' => 'shaker-pro-600ml',
                'description' => 'Vaso mezclador con compartimiento para proteína, ideal para tus batidos post entrenamiento.',
                'short_description' => 'Shaker con compartimientos',
                'price_soles' => 24.99,
                'compare_price_soles' => 34.99,

                'stock_quantity' => 150,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chaqueta Impermeable',
                'sku' => '3543550',
                'slug' => 'chaqueta-impermeable',
                'description' => 'Chaqueta ligera resistente al agua, perfecta para correr en climas lluviosos.',
                'short_description' => 'Chaqueta running impermeable',
                'price_soles' => 149.99,
                'compare_price_soles' => 179.99,

                'stock_quantity' => 45,
                'status' => 'active',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BCAA Recovery 300g',
                'sku' => '3543551',
                'slug' => 'bcaa-recovery-300g',
                'description' => 'Aminoácidos esenciales para recuperación y energía durante el entrenamiento.',
                'short_description' => 'BCAA sabor mango',
                'price_soles' => 89.90,
                'compare_price_soles' => 109.90,

                'stock_quantity' => 70,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Faja Térmica Abdominal',
                'sku' => '3543552',
                'slug' => 'faja-termica-abdominal',
                'description' => 'Faja ajustable con efecto calor para mayor sudoración en la zona abdominal.',
                'short_description' => 'Faja térmica para entrenar',
                'price_soles' => 59.99,
                'compare_price_soles' => 69.99,

                'stock_quantity' => 85,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cuerda de Salto Pro',
                'sku' => '3543553',
                'slug' => 'cuerda-de-salto-pro',
                'description' => 'Cuerda con rodamientos metálicos para saltos rápidos y alta intensidad.',
                'short_description' => 'Cuerda profesional de velocidad',
                'price_soles' => 34.99,
                'compare_price_soles' => 44.99,

                'stock_quantity' => 130,
                'status' => 'active',
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ];

        Product::insert($products);

        // Fin productos
    }
}
