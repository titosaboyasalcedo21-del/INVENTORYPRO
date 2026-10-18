<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const SECTIONS = [
        'categories' => ['model' => Category::class, 'singular' => 'categoría', 'plural' => 'Categorías', 'icon' => 'bi-tags'],
        'suppliers' => ['model' => Supplier::class, 'singular' => 'proveedor', 'plural' => 'Proveedores', 'icon' => 'bi-truck'],
        'customers' => ['model' => Customer::class, 'singular' => 'cliente', 'plural' => 'Clientes', 'icon' => 'bi-people'],
    ];

    public function index(Request $request): View
    {
        $section = $this->section($request);
        $model = $section['model'];
        $query = $model::query()->latest();
        if ($section['key'] === 'categories') {
            $query->withCount('products');
        }
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search, $section) {
                $builder->where('name', 'like', "%{$search}%");

                if ($section['model'] !== Category::class) {
                    $builder->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                }
            });
        }

        return view('catalog.index', [
            'section' => $section,
            'records' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        return view('catalog.form', ['section' => $this->section($request), 'record' => null]);
    }

    public function store(CatalogRequest $request): RedirectResponse
    {
        $section = $this->section($request);
        $model = $section['model'];
        $model::create($request->validated() + ['created_by' => $request->user()->id, 'is_active' => true]);

        return redirect()->route("{$section['key']}.index")->with('status', ucfirst($section['singular']).' creado correctamente.');
    }

    public function edit(Request $request): View
    {
        $section = $this->section($request);
        $model = $section['model'];

        return view('catalog.form', [
            'section' => $section,
            'record' => $model::findOrFail($this->recordId($request)),
        ]);
    }

    public function update(CatalogRequest $request): RedirectResponse
    {
        $section = $this->section($request);
        $model = $section['model'];
        $model::findOrFail($this->recordId($request))->update($request->validated());

        return redirect()->route("{$section['key']}.index")->with('status', ucfirst($section['singular']).' actualizado correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $section = $this->section($request);
        $model = $section['model'];
        $record = $model::findOrFail($this->recordId($request));

        if ($record instanceof Category && $record->products()->where('is_active', true)->exists()) {
            return back()->withErrors(['record' => 'Desactiva o reasigna primero los productos activos de esta categoría.']);
        }

        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('status', ucfirst($section['singular']).($record->is_active ? ' activado.' : ' desactivado.'));
    }

    private function section(Request $request): array
    {
        $key = explode('.', (string) $request->route()->getName())[0] ?? '';

        abort_unless(isset(self::SECTIONS[$key]), 404);

        return ['key' => $key] + self::SECTIONS[$key];
    }

    private function recordId(Request $request): int
    {
        $section = $this->section($request);
        $parameter = match ($section['key']) {
            'categories' => 'category',
            'suppliers' => 'supplier',
            'customers' => 'customer',
        };

        return (int) $request->route($parameter);
    }
}
