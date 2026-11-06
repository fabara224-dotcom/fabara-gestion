<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ton espace de propriétaire de la plateforme :
 * toutes les entreprises clientes, leurs abonnements, les paiements de location.
 * Tu ne vois que des chiffres globaux ; pour aider un client tu peux "entrer" dans son espace.
 */
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with('plan')->withCount('users')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('slug', 'like', '%'.$request->q.'%')))
            ->when($request->filled('statut'), fn ($q) => $q->where('status', $request->statut))
            ->latest()->paginate(30)->withQueryString();

        $mrr = (int) Tenant::where('status', 'active')->join('plans', 'plans.id', '=', 'tenants.plan_id')->sum('plans.price_gnf');

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'stats' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'trial' => Tenant::where('status', 'trial')->count(),
                'late' => Tenant::whereIn('status', ['past_due', 'suspended'])->count(),
                'mrr' => $mrr,
                'month_payments' => (int) SubscriptionPayment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'new_month' => Tenant::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function show(Tenant $tenant)
    {
        $sales = Sale::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id);

        return view('admin.tenants.show', [
            'tenant' => $tenant->load(['plan', 'users', 'branches', 'payments.plan']),
            'plans' => Plan::orderBy('price_gnf')->get(),
            'usage' => [
                'products' => Product::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
                'sales_month' => (clone $sales)->where('sold_at', '>=', now()->startOfMonth())->count(),
                'last_sale' => (clone $sales)->max('sold_at'),
            ],
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['trial', 'active', 'past_due', 'suspended'])],
            'plan_id' => ['required', 'exists:plans,id'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);
        $tenant->update($data);

        return back()->with('success', 'Entreprise mise à jour.');
    }

    /** Enregistre un paiement de location (Orange Money, MoMo, espèces) et prolonge l'abonnement */
    public function payment(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'months' => ['required', 'integer', 'min:1', 'max:24'],
            'method' => ['required', Rule::in(['orange_money', 'mtn_momo', 'cash', 'virement'])],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);
        $plan = Plan::find($data['plan_id']);

        SubscriptionPayment::create([...$data, 'tenant_id' => $tenant->id, 'amount' => $plan->price_gnf * $data['months'], 'paid_at' => now()]);

        $start = $tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture() ? $tenant->subscription_ends_at : now();
        $tenant->update([
            'plan_id' => $plan->id,
            'status' => 'active',
            'subscription_ends_at' => $start->copy()->addMonths($data['months']),
        ]);

        return back()->with('success', 'Paiement enregistré. Abonnement actif jusqu\'au '.$tenant->subscription_ends_at->format('d/m/Y').'.');
    }

    public function impersonate(Request $request, Tenant $tenant)
    {
        $request->session()->put('impersonate_tenant', $tenant->id);

        return redirect()->route('dashboard')->with('success', 'Vous êtes dans l\'espace de '.$tenant->name.' (mode support).');
    }

    public function leave(Request $request)
    {
        $request->session()->forget('impersonate_tenant');

        return redirect()->route('admin.tenants.index');
    }
}
