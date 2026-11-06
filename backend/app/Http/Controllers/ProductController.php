<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('sku', 'like', '%'.$request->q.'%')
                ->orWhere('barcode', $request->q)))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->category))
            ->when($request->input('stock') === 'low', fn ($q) => $q->where('track_stock', true)->whereColumn('stock', '<=', 'alert_threshold'))
            ->orderBy('name')
            ->paginate(24)->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'stockValue' => (int) Product::where('track_stock', true)->where('stock', '>', 0)->sum(DB::raw('stock * cost')),
        ]);
    }

    public function create()
    {
        $this->checkPlanLimit();

        return view('products.form', ['product' => new Product(['unit' => 'pièce', 'alert_threshold' => 3, 'track_stock' => true]),
            'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->checkPlanLimit();
        $data = $this->validated($request);

        $product = Product::create($data);
        $this->handleImages($request, $product);

        $initial = (float) $request->input('initial_stock', 0);
        if ($initial != 0) {
            StockMovement::create([
                'product_id' => $product->id, 'branch_id' => $request->user()->branch_id, 'user_id' => $request->user()->id,
                'type' => 'in', 'quantity' => $initial, 'reason' => 'Stock initial',
            ]);
            $product->recalculateStock();
        }

        return redirect()->route($request->boolean('add_another') ? 'products.create' : 'products.index')
            ->with('success', '« '.$product->name.' » ajouté.');
    }

    public function edit(Product $product)
    {
        return view('products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request, $product));
        $this->handleImages($request, $product);

        return redirect()->route('products.index')->with('success', 'Article mis à jour.');
    }

    public function destroy(Product $product)
    {
        $product->delete(); // suppression douce : l'historique des ventes reste intact

        return back()->with('success', 'Article supprimé.');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'price' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:60'],
            'unit' => ['nullable', 'string', 'max:20'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'gallery.*' => ['nullable', 'image', 'max:5120'],
            'initial_stock' => ['nullable', 'numeric'],
        ]);
        $data['track_stock'] = $request->boolean('track_stock');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['cost'] = $data['cost'] ?? 0;
        $data['unit'] = $data['unit'] ?: 'pièce';
        $data['alert_threshold'] = $data['alert_threshold'] ?? 0;
        unset($data['image'], $data['gallery'], $data['initial_stock']);

        return $data;
    }

    /** Photos rangées par entreprise : products/{tenant_id}/… */
    protected function handleImages(Request $request, Product $product): void
    {
        $dir = 'products/'.$product->tenant_id;

        if ($request->hasFile('image')) {
            if ($product->image_path) Storage::disk('public')->delete($product->image_path);
            $product->image_path = $request->file('image')->store($dir, 'public');
        }
        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $product->image_path = null;
        }
        if ($request->hasFile('gallery')) {
            $gallery = $product->gallery ?? [];
            foreach (array_slice($request->file('gallery'), 0, 6) as $file) {
                $gallery[] = $file->store($dir, 'public');
            }
            $product->gallery = array_slice($gallery, -6);
        }
        $product->save();
    }

    protected function checkPlanLimit(): void
    {
        $plan = app(CurrentTenant::class)->get()?->plan;
        if ($plan && Product::count() >= $plan->max_products) {
            abort(403, 'Limite de '.$plan->max_products.' articles atteinte pour votre formule. Passez à la formule supérieure.');
        }
    }
}
