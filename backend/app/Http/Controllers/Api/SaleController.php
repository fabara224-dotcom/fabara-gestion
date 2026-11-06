<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /** Historique serveur (ventes de toutes les caisses : utile au propriétaire) */
    public function index(Request $request)
    {
        $user = $request->user();

        return Sale::with(['items', 'user:id,name'])
            ->when($user->scopedBranchId(), fn ($q, $b) => $q->where('branch_id', $b))
            ->when($user->role === 'cashier', fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('sold_at', $request->date))
            ->latest('sold_at')->paginate(50)
            ->through(fn ($s) => [...$s->toArray(), 'receipt_url' => $s->publicUrl(), 'seller' => $s->user?->name]);
    }

    public function show(Request $request, Sale $sale)
    {
        return [...$sale->load('items')->toArray(), 'receipt_url' => $sale->publicUrl()];
    }
}
