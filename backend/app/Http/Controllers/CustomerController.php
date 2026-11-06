<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

/** Clients et crédits ("dettes") : très courant dans les boutiques de quartier. */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return view('customers.index', [
            'customers' => Customer::withCount('sales')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
                ->when($request->input('filtre') === 'credit', fn ($q) => $q->where('balance', '>', 0))
                ->orderByDesc('balance')->orderBy('name')->paginate(30)->withQueryString(),
            'totalDebt' => (int) Customer::where('balance', '>', 0)->sum('balance'),
        ]);
    }

    /** Le client rembourse tout ou partie de son crédit */
    public function pay(Request $request, Customer $customer)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:'.max(1, $customer->balance)]]);
        $customer->decrement('balance', $data['amount']);

        return back()->with('success', 'Paiement de '.\App\Support\Money::format($data['amount']).' enregistré pour '.$customer->name.'.');
    }
}
