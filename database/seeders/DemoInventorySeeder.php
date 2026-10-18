<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoInventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $userId = User::query()->orderBy('id')->value('id');
            $categories = [];

            foreach ([
                ['name' => 'Abarrotes', 'description' => 'Alimentos y productos de consumo diario.'],
                ['name' => 'Bebidas', 'description' => 'Agua y bebidas envasadas.'],
                ['name' => 'Limpieza', 'description' => 'Productos para limpieza del hogar.'],
                ['name' => 'Cuidado personal', 'description' => 'Artículos de higiene y cuidado personal.'],
            ] as $data) {
                $categories[$data['name']] = Category::firstOrCreate(
                    ['name' => $data['name']],
                    ['description' => $data['description'], 'created_by' => $userId],
                );
            }

            foreach ([
                ['name' => 'Distribuidora Andina Demo', 'company' => 'Andina Demo S.A.C.', 'email' => 'andina@example.test', 'phone' => '900000101', 'address' => 'Datos ficticios de demostración'],
                ['name' => 'Mayorista Lima Demo', 'company' => 'Mayorista Lima Demo S.A.C.', 'email' => 'mayorista@example.test', 'phone' => '900000102', 'address' => 'Datos ficticios de demostración'],
                ['name' => 'Suministros del Pacífico Demo', 'company' => 'Pacífico Demo S.A.C.', 'email' => 'suministros@example.test', 'phone' => '900000103', 'address' => 'Datos ficticios de demostración'],
            ] as $data) {
                Supplier::firstOrCreate(
                    ['email' => $data['email']],
                    $data + ['is_active' => true, 'created_by' => $userId],
                );
            }

            foreach ([
                ['name' => 'Cliente de demostración Uno', 'email' => 'cliente1@example.test', 'phone' => '900000201'],
                ['name' => 'Cliente de demostración Dos', 'email' => 'cliente2@example.test', 'phone' => '900000202'],
                ['name' => 'Cliente de demostración Tres', 'email' => 'cliente3@example.test', 'phone' => '900000203'],
            ] as $data) {
                Customer::firstOrCreate(
                    ['email' => $data['email']],
                    $data + ['is_active' => true, 'created_by' => $userId, 'address' => 'Datos ficticios de demostración'],
                );
            }

            $products = [
                ['sku' => 'DEMO-ABR-001', 'name' => 'Arroz extra 1 kg', 'category' => 'Abarrotes', 'purchase_price' => '4.20', 'sale_price' => '5.90', 'stock' => 50, 'minimum_stock' => 10],
                ['sku' => 'DEMO-ABR-002', 'name' => 'Aceite vegetal 1 L', 'category' => 'Abarrotes', 'purchase_price' => '7.80', 'sale_price' => '10.50', 'stock' => 35, 'minimum_stock' => 8],
                ['sku' => 'DEMO-BEB-001', 'name' => 'Agua mineral 625 ml', 'category' => 'Bebidas', 'purchase_price' => '1.20', 'sale_price' => '2.00', 'stock' => 80, 'minimum_stock' => 20],
                ['sku' => 'DEMO-LIM-001', 'name' => 'Detergente 1 kg', 'category' => 'Limpieza', 'purchase_price' => '8.50', 'sale_price' => '12.90', 'stock' => 24, 'minimum_stock' => 6],
                ['sku' => 'DEMO-CUI-001', 'name' => 'Jabón de tocador 120 g', 'category' => 'Cuidado personal', 'purchase_price' => '2.40', 'sale_price' => '3.90', 'stock' => 40, 'minimum_stock' => 10],
            ];

            foreach ($products as $data) {
                $product = Product::firstOrNew(['sku' => $data['sku']]);
                if ($product->exists) {
                    continue;
                }

                $initialStock = $data['stock'];
                $product->fill([
                    'name' => $data['name'],
                    'category_id' => $categories[$data['category']]->id,
                    'purchase_price' => $data['purchase_price'],
                    'sale_price' => $data['sale_price'],
                    'stock' => 0,
                    'minimum_stock' => $data['minimum_stock'],
                    'is_active' => true,
                    'created_by' => $userId,
                ])->save();

                app(StockLedger::class)->adjust(
                    $product,
                    $initialStock,
                    'adjustment',
                    null,
                    'Existencia inicial de demostración (en soles PEN).',
                );
            }
        });
    }
}
