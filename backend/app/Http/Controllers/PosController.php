<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'category_id', 'image_path', 'barcode', 'track_stock', 'unit']);

        return view('pos.index', [
            'products' => $products->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'price' => $p->price, 'stock' => $p->stock,
                'category_id' => $p->category_id, 'image' => $p->image_url, 'barcode' => $p->barcode,
                'track' => $p->track_stock, 'unit' => $p->unit,
            ]),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'methods' => Sale::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request, SaleService $service)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(array_keys(Sale::PAYMENT_METHODS))],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
        ]);

        // Sur le web, le prix vient toujours de la fiche article (pas du navigateur)
        $data['items'] = array_map(fn ($i) => ['product_id' => $i['product_id'], 'quantity' => $i['quantity']], $data['items']);

        $sale = $service->create($request->user(), $data);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('sales.show', $sale)]);
        }

        return redirect()->route('sales.show', $sale)->with('success', 'Vente enregistrée.');
    }
}
