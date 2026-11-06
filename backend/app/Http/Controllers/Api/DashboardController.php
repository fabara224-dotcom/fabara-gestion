<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Vue "œil du propriétaire" dans l'application : chiffres en direct depuis le serveur. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $branchId = $user->scopedBranchId();
        $base = fn () => Sale::where('status', 'completed')->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $profit = (int) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')->where('sales.sold_at', '>=', now()->startOfMonth())
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->sum(DB::raw('sale_items.total - sale_items.unit_cost * sale_items.quantity'));

        $daily = $base()->where('sold_at', '>=', today()->subDays(6))
            ->selectRaw('DATE(sold_at) d, SUM(total) t')->groupBy('d')->pluck('t', 'd');

        $team = [];
        if ($user->isOwner()) {
            $team = User::where('tenant_id', app(CurrentTenant::class)->id())->where('role', '!=', 'owner')->get()
                ->map(fn ($u) => [
                    'name' => $u->name, 'role' => $u->roleLabel(), 'active' => $u->is_active,
                    'last_seen' => $u->last_seen_at?->diffForHumans(),
                    'today_total' => (int) Sale::where('user_id', $u->id)->where('status', 'completed')->whereDate('sold_at', today())->sum('total'),
                ])->values();
        }

        return response()->json([
            'today_total' => (int) $base()->whereDate('sold_at', today())->sum('total'),
            'today_count' => $base()->whereDate('sold_at', today())->count(),
            'month_total' => (int) $base()->where('sold_at', '>=', now()->startOfMonth())->sum('total'),
            'month_profit' => $profit,
            'low_stock' => Product::where('track_stock', true)->whereColumn('stock', '<=', 'alert_threshold')->count(),
            'week' => collect(range(6, 0))->map(fn ($i) => [
                'day' => today()->subDays($i)->translatedFormat('D'),
                'total' => (int) ($daily[today()->subDays($i)->toDateString()] ?? 0),
            ]),
            'team' => $team,
        ]);
    }
}
