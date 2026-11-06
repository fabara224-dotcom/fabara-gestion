<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de bord.
 *  - Propriétaire : vue d'ensemble de SON entreprise (toutes boutiques, chaque vendeur, bénéfice).
 *  - Gérant / vendeur : les chiffres de sa boutique et ses propres ventes.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $tenant = app(CurrentTenant::class)->get();

        if ($user->isSuperAdmin() && ! $tenant) {
            return redirect()->route('admin.tenants.index');
        }

        $branchId = $user->scopedBranchId();
        $sales = fn () => Sale::where('status', 'completed')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $today = $sales()->whereDate('sold_at', today());
        $month = $sales()->where('sold_at', '>=', now()->startOfMonth());

        $stats = [
            'today_total' => (int) (clone $today)->sum('total'),
            'today_count' => (clone $today)->count(),
            'month_total' => (int) (clone $month)->sum('total'),
            'month_count' => (clone $month)->count(),
            'month_profit' => $this->profit(now()->startOfMonth(), $branchId),
            'low_stock' => Product::where('track_stock', true)->whereColumn('stock', '<=', 'alert_threshold')->count(),
            'products' => Product::count(),
        ];

        // Ventes des 14 derniers jours (graphique)
        $daily = $sales()->where('sold_at', '>=', today()->subDays(13))
            ->selectRaw('DATE(sold_at) as d, SUM(total) as t, COUNT(*) as c')
            ->groupBy('d')->pluck('t', 'd');
        $chart = collect(range(13, 0))->map(fn ($i) => [
            'label' => today()->subDays($i)->translatedFormat('D d'),
            'value' => (int) ($daily[today()->subDays($i)->toDateString()] ?? 0),
        ]);

        $data = [
            'stats' => $stats,
            'chart' => $chart,
            'recent' => $sales()->with('user')->latest('sold_at')->limit(8)->get(),
            'lowProducts' => Product::where('track_stock', true)->whereColumn('stock', '<=', 'alert_threshold')
                ->orderBy('stock')->limit(6)->get(),
            'topProducts' => SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', 'completed')
                ->where('sales.sold_at', '>=', now()->startOfMonth())
                ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
                ->selectRaw('sale_items.name, SUM(sale_items.quantity) as qty, SUM(sale_items.total) as amount')
                ->groupBy('sale_items.name')->orderByDesc('amount')->limit(5)->get(),
        ];

        // Vue propriétaire : activité de chaque vendeur aujourd'hui + boutiques
        if ($user->isOwner() || $user->isSuperAdmin()) {
            $data['team'] = User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')
                ->with('branch')->get()->map(function ($u) {
                    $q = Sale::where('user_id', $u->id)->where('status', 'completed')->whereDate('sold_at', today());
                    $u->today_total = (int) (clone $q)->sum('total');
                    $u->today_count = $q->count();
                    return $u;
                })->sortByDesc('today_total');

            $data['branches'] = $tenant->branches()->get()->map(function ($b) {
                $b->today_total = (int) Sale::where('branch_id', $b->id)->where('status', 'completed')->whereDate('sold_at', today())->sum('total');
                return $b;
            });

            $data['payments'] = Sale::where('status', 'completed')->where('sold_at', '>=', now()->startOfMonth())
                ->selectRaw('payment_method, SUM(total) as t')->groupBy('payment_method')->pluck('t', 'payment_method');
        }

        return view('dashboard', $data);
    }

    /** Bénéfice brut = ventes - coût d'achat des articles vendus */
    protected function profit($from, ?int $branchId): int
    {
        return (int) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->where('sales.sold_at', '>=', $from)
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->sum(DB::raw('sale_items.total - sale_items.unit_cost * sale_items.quantity'));
    }
}
