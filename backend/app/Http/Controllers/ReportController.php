<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()->scopedBranchId();
        [$from, $to, $period] = $this->period($request);

        $sales = Sale::where('sales.status', 'completed')->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId));

        $items = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId));

        $revenue = (int) (clone $sales)->sum('total');
        $cost = (int) (clone $items)->sum(DB::raw('sale_items.unit_cost * sale_items.quantity'));
        $discounts = (int) (clone $sales)->sum('discount');
        $expenses = (int) Expense::whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->sum('amount');

        $byDay = (clone $sales)->selectRaw('DATE(sales.sold_at) d, SUM(total) t')->groupBy('d')->orderBy('d')->pluck('t', 'd');

        return view('reports.index', [
            'period' => $period, 'from' => $from, 'to' => $to,
            'revenue' => $revenue,
            'count' => (clone $sales)->count(),
            'cost' => $cost,
            'gross' => $revenue - $cost,
            'expenses' => $expenses,
            'net' => $revenue - $cost - $expenses,
            'discounts' => $discounts,
            'average' => (clone $sales)->count() ? (int) ($revenue / (clone $sales)->count()) : 0,
            'byDay' => $byDay,
            'byMethod' => (clone $sales)->selectRaw('payment_method, SUM(total) t, COUNT(*) c')->groupBy('payment_method')->get(),
            'bySeller' => (clone $sales)->join('users', 'users.id', '=', 'sales.user_id')
                ->selectRaw('users.name, SUM(sales.total) t, COUNT(*) c')->groupBy('users.name')->orderByDesc('t')->get(),
            'top' => (clone $items)->selectRaw('sale_items.name, SUM(sale_items.quantity) q, SUM(sale_items.total) t, SUM(sale_items.total - sale_items.unit_cost * sale_items.quantity) m')
                ->groupBy('sale_items.name')->orderByDesc('t')->limit(10)->get(),
        ]);
    }

    protected function period(Request $request): array
    {
        $p = $request->input('periode', 'mois');

        return match ($p) {
            'jour' => [today()->startOfDay(), today()->endOfDay(), $p],
            'semaine' => [now()->startOfWeek(), now()->endOfWeek(), $p],
            'annee' => [now()->startOfYear(), now()->endOfYear(), $p],
            'perso' => [Carbon::parse($request->input('du', today()))->startOfDay(), Carbon::parse($request->input('au', today()))->endOfDay(), $p],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'mois'],
        };
    }
}
