<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_and_product_crud_are_authenticated_and_validated(): void
    {
        $user = User::factory()->create();

        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Bebidas',
            'description' => 'Líquidos',
        ])->assertRedirect(route('categories.index'));

        $category = Category::firstOrFail();
        $this->post(route('suppliers.store'), ['name' => 'Distribuidora Uno', 'email' => 'ventas@example.test'])
            ->assertRedirect(route('suppliers.index'));
        $this->post(route('customers.store'), ['name' => 'Cliente Uno'])
            ->assertRedirect(route('customers.index'));

        $this->post(route('products.store'), [
            'sku' => 'BEB-001',
            'name' => 'Agua',
            'category_id' => $category->id,
            'purchase_price' => '4.50',
            'sale_price' => '8.00',
            'minimum_stock' => 3,
        ])->assertRedirect(route('products.index'));

        $product = Product::firstOrFail();
        $this->assertSame('0', (string) $product->stock);
        $this->assertSame(1, Supplier::count());
        $this->assertSame(1, Customer::count());

        $this->from(route('products.create'))->post(route('products.store'), [
            'sku' => 'BEB-001',
            'name' => 'Agua duplicada',
            'purchase_price' => '4.50',
            'sale_price' => '8.00',
            'minimum_stock' => 0,
        ])->assertSessionHasErrors('sku');

        $this->delete(route('products.destroy', $product))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_receiving_a_purchase_increments_stock_only_once_and_cancellation_compensates_it(): void
    {
        $this->actingAs(User::factory()->create());
        config(['inventory.tax_rate' => '10.00']);
        $supplier = Supplier::create(['name' => 'Proveedor', 'is_active' => true]);
        $product = $this->product(stock: 0, purchasePrice: '5.00');
        $key = (string) Str::uuid();

        $this->post(route('purchases.store'), [
            'request_key' => $key,
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => '7.25']],
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::firstOrFail();
        $this->assertSame('pending', $purchase->status);
        $this->assertSame('0', (string) $product->fresh()->stock);
        $this->assertSame('31.90', $purchase->total);

        $this->post(route('purchases.receive', $purchase))->assertSessionHasNoErrors();
        $this->assertSame('4', (string) $product->fresh()->stock);
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame(1, StockMovement::where('type', 'purchase_received')->count());

        $this->post(route('purchases.receive', $purchase))->assertSessionHasErrors('purchase');
        $this->assertSame('4', (string) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('type', 'purchase_received')->count());

        $this->post(route('purchases.cancel', $purchase))->assertSessionHasNoErrors();
        $this->assertSame('0', (string) $product->fresh()->stock);
        $this->assertSame('cancelled', $purchase->fresh()->status);
        $this->assertSame(1, StockMovement::where('type', 'purchase_cancelled')->count());
    }

    public function test_sale_recalculates_server_prices_persists_payment_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        config(['inventory.tax_rate' => '10.00']);
        $customer = Customer::create(['name' => 'Cliente', 'is_active' => true]);
        $product = $this->product(stock: 5, salePrice: '20.00');
        $key = (string) Str::uuid();
        $payload = [
            'request_key' => $key,
            'customer_id' => $customer->id,
            'discount_amount' => '5.00',
            'payment_method' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $this->post(route('sales.store'), $payload)->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->assertSame('40.00', $sale->subtotal);
        $this->assertSame('5.00', $sale->discount_amount);
        $this->assertSame('3.50', $sale->tax_amount);
        $this->assertSame('38.50', $sale->total);
        $this->assertSame('card', $sale->payments()->firstOrFail()->method);
        $this->assertSame('38.50', $sale->payments()->firstOrFail()->amount);
        $this->assertSame('3', (string) $product->fresh()->stock);

        $this->post(route('sales.store'), $payload)->assertRedirect(route('sales.show', $sale));
        $this->assertSame(1, Sale::count());
        $this->assertSame('3', (string) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('type', 'sale')->count());

        $this->post(route('sales.cancel', $sale))->assertSessionHasNoErrors();
        $this->assertSame('5', (string) $product->fresh()->stock);
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame('-38.50', Payment::where('method', 'refund')->firstOrFail()->amount);
        $this->assertSame(1, AuditLog::where('action', 'sale.cancelled')->count());
    }

    public function test_sale_refuses_stock_shortage_and_repeated_form_submissions_do_not_duplicate_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 1, salePrice: '10.00');

        $this->from(route('sales.create'))->post(route('sales.store'), [
            'request_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('items');
        $this->assertSame('1', (string) $product->fresh()->stock);
        $this->assertSame(0, Sale::count());

        $key = (string) Str::uuid();
        $payload = ['request_key' => $key, 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]];
        $this->post(route('sales.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('sales.store'), $payload)->assertRedirect();
        $this->assertSame(1, Sale::count());
        $this->assertSame('0', (string) $product->fresh()->stock);
    }

    public function test_reports_filter_real_data_and_export_csv_and_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 2, salePrice: '15.00');
        $this->post(route('sales.store'), [
            'request_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $filters = ['from' => today()->toDateString(), 'to' => today()->toDateString()];
        $this->get(route('reports.index', $filters))
            ->assertOk()->assertSee('15.00')->assertSee('Ventas por día');
        $this->get(route('reports.csv', $filters))->assertDownload();
        $this->get(route('reports.pdf', $filters))->assertDownload();
        $this->get(route('reports.index', ['from' => 'not-a-date', 'to' => today()->toDateString()]))
            ->assertSessionHasErrors('from');
        $this->get(route('reports.index', ['from' => '', 'to' => '']))->assertOk();
        $this->assertSame(1, Sale::count());
    }

    public function test_all_navigation_destinations_and_forms_render_for_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'Varios', 'is_active' => true]);
        $supplier = Supplier::create(['name' => 'Proveedor', 'is_active' => true]);
        $customer = Customer::create(['name' => 'Cliente', 'is_active' => true]);
        $product = $this->product(stock: 2);
        $product->update(['category_id' => $category->id]);

        foreach ([
            route('products.index'), route('products.create'), route('products.edit', $product), route('products.show', $product),
            route('categories.index'), route('categories.create'), route('categories.edit', $category),
            route('suppliers.index'), route('suppliers.create'), route('suppliers.edit', $supplier),
            route('customers.index'), route('customers.create'), route('customers.edit', $customer),
            route('purchases.index'), route('purchases.create'),
            route('sales.index'), route('sales.create'),
            route('inventory.movements.index'), route('reports.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_stock_adjustments_are_logged_and_categories_with_active_products_cannot_be_deactivated(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::create(['name' => 'General', 'is_active' => true]);
        $product = $this->product(stock: 0);
        $product->update(['category_id' => $category->id]);

        $this->delete(route('categories.destroy', $category))
            ->assertSessionHasErrors('record');
        $this->post(route('inventory.adjustments.store'), [
            'product_id' => $product->id,
            'direction' => 'in',
            'quantity' => 6,
            'description' => 'Conteo inicial',
        ])->assertSessionHasNoErrors();

        $this->assertSame('6', (string) $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 6,
            'stock_before' => 0,
            'stock_after' => 6,
        ]);

        $this->post(route('inventory.adjustments.store'), [
            'product_id' => $product->id,
            'direction' => 'out',
            'quantity' => 7,
            'description' => 'Salida no permitida',
        ])->assertSessionHasErrors('quantity');
        $this->assertSame('6', (string) $product->fresh()->stock);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_demo_seeder_adds_sample_catalog_and_stock_once(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Category::count());
        $this->assertSame(3, Supplier::count());
        $this->assertSame(3, Customer::count());
        $this->assertSame(5, Product::count());
        $this->assertSame(5, StockMovement::where('type', 'adjustment')->count());
        $this->assertSame(5, AuditLog::where('action', 'stock.adjustment')->count());
        $this->assertSame('50', (string) Product::where('sku', 'DEMO-ABR-001')->value('stock'));

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Category::count());
        $this->assertSame(3, Supplier::count());
        $this->assertSame(3, Customer::count());
        $this->assertSame(5, Product::count());
        $this->assertSame(5, StockMovement::where('type', 'adjustment')->count());
        $this->assertSame('50', (string) Product::where('sku', 'DEMO-ABR-001')->value('stock'));
    }

    private function product(int $stock, string $salePrice = '20.00', string $purchasePrice = '5.00'): Product
    {
        return Product::create([
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'name' => 'Producto de prueba',
            'sale_price' => $salePrice,
            'purchase_price' => $purchasePrice,
            'stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
    }
}
