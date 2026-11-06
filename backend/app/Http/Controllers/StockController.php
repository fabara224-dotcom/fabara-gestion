<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function index(Request $request)
    {
        return view('stock.index', [
            'movements' => StockMovement::with(['product', 'user'])
                ->when($request->filled('product'), fn ($q) => $q->where('product_id', $request->product))
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->latest()->paginate(30)->withQueryString(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'stock', 'unit']),
            'types' => StockMovement::TYPES,
            'selected' => $request->product,
        ]);
    }

    /**
     * Entrée / sortie / ajustement.
     * Pour un ajustement, on saisit la quantité RÉELLE comptée ; l'écart est enregistré.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', app(CurrentTenant::class)->id())],
            'type' => ['required', Rule::in(['in', 'out', 'adjust'])],
            'quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = (float) $data['quantity'];
        $delta = match ($data['type']) {
            'in' => $qty,
            'out' => -$qty,
            'adjust' => $qty - $product->stock,
        };

        if ($delta == 0) {
            return back()->with('success', 'Le stock est déjà juste, aucun changement.');
        }

        StockMovement::create([
            'product_id' => $product->id, 'branch_id' => $request->user()->branch_id, 'user_id' => $request->user()->id,
            'type' => $data['type'], 'quantity' => $delta, 'reason' => $data['reason'],
        ]);
        $product->recalculateStock();

        return back()->with('success', 'Stock de « '.$product->name.' » : '.\App\Support\Money::qty($product->stock).' '.$product->unit.'.');
    }
}
