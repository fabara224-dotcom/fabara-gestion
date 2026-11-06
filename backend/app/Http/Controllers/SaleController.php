<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\User;
use App\Services\ReceiptService;
use App\Services\SaleService;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $user->scopedBranchId();

        $query = Sale::with(['user', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($user->role === 'cashier', fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->date('to')))
            ->when($request->filled('seller'), fn ($q) => $q->where('user_id', $request->integer('seller')))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->input('method')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$request->q.'%')
                ->orWhere('customer_name', 'like', '%'.$request->q.'%')
                ->orWhere('customer_phone', 'like', '%'.$request->q.'%')));

        $totals = (clone $query)->where('status', 'completed')->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) t')->first();

        return view('sales.index', [
            'sales' => $query->latest('sold_at')->paginate(25)->withQueryString(),
            'totals' => $totals,
            'sellers' => User::where('tenant_id', app(CurrentTenant::class)->id())->orderBy('name')->get(['id', 'name']),
            'methods' => Sale::PAYMENT_METHODS,
        ]);
    }

    public function show(Request $request, Sale $sale)
    {
        $this->authorizeView($request->user(), $sale);

        return view('sales.show', ['sale' => $sale->load(['items', 'user', 'branch'])]);
    }

    /** Reçu PDF : ?format=ticket (80 mm) ou ?format=a4 ; ?download=1 pour télécharger */
    public function receipt(Request $request, Sale $sale, ReceiptService $receipts)
    {
        $this->authorizeView($request->user(), $sale);
        $pdf = $receipts->pdf($sale, $request->query('format', 'ticket'));

        return $request->boolean('download')
            ? $pdf->download($receipts->filename($sale))
            : $pdf->stream($receipts->filename($sale));
    }

    public function cancel(Request $request, Sale $sale, SaleService $service)
    {
        $service->cancel($sale, $request->user());

        return back()->with('success', 'Vente '.$sale->number.' annulée, articles remis en stock.');
    }

    protected function authorizeView(User $user, Sale $sale): void
    {
        if ($user->role === 'cashier' && $sale->user_id !== $user->id) abort(403);
        if ($user->role === 'manager' && $user->branch_id && $sale->branch_id !== $user->branch_id) abort(403);
    }
}
