<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\SaleService;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Synchronisation de l'application mobile hors ligne.
 *
 *  GET  /api/v1/sync/pull?since=2026-11-06T10:00:00Z
 *       → tout ce qui a changé depuis la dernière synchro (articles, catégories, clients),
 *         y compris les suppressions (deleted_at), + l'heure serveur à garder pour la prochaine fois.
 *
 *  POST /api/v1/sync/push
 *       → envoie les ventes, articles, mouvements de stock et dépenses créés hors ligne.
 *         Chaque élément a un UUID généré sur le téléphone : renvoyer deux fois ne crée pas de doublon.
 */
class SyncController extends Controller
{
    public function pull(Request $request)
    {
        $since = $request->filled('since') ? Carbon::parse($request->since) : null;
        $serverTime = now();
        $changed = fn ($q) => $since ? $q->where('updated_at', '>', $since) : $q;

        $tenant = app(CurrentTenant::class)->get();

        return response()->json([
            'server_time' => $serverTime->toIso8601String(),
            'tenant' => [
                'name' => $tenant->name, 'logo_url' => $tenant->logoUrl(), 'brand_color' => $tenant->brand_color,
                'phone' => $tenant->phone, 'address' => $tenant->address, 'city' => $tenant->city,
                'receipt_header' => $tenant->receipt_header, 'receipt_footer' => $tenant->receipt_footer,
                'tax_id' => $tenant->tax_id, 'currency' => $tenant->currency, 'status' => $tenant->status,
                'days_left' => $tenant->daysLeft(),
            ],
            'categories' => $changed(Category::withTrashed())->get(['id', 'name', 'color', 'updated_at', 'deleted_at']),
            'products' => $changed(Product::withTrashed())->limit(20000)->get()->map(fn ($p) => [
                'id' => $p->id, 'category_id' => $p->category_id, 'name' => $p->name, 'sku' => $p->sku,
                'barcode' => $p->barcode, 'price' => $p->price, 'cost' => $p->cost, 'unit' => $p->unit,
                'stock' => $p->stock, 'alert_threshold' => $p->alert_threshold, 'image_url' => $p->image_url,
                'track_stock' => $p->track_stock, 'is_active' => $p->is_active,
                'updated_at' => $p->updated_at?->toIso8601String(), 'deleted_at' => $p->deleted_at?->toIso8601String(),
            ]),
            'customers' => $changed(Customer::withTrashed())->get(['id', 'name', 'phone', 'balance', 'updated_at', 'deleted_at']),
        ]);
    }

    public function push(Request $request, SaleService $sales)
    {
        $request->validate([
            'device_id' => ['required', 'string', 'max:64'],
            'products' => ['array', 'max:500'],
            'products.*.id' => ['required', 'uuid'],
            'sales' => ['array', 'max:500'],
            'sales.*.id' => ['required', 'uuid'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'stock_movements' => ['array', 'max:1000'],
            'stock_movements.*.id' => ['required', 'uuid'],
            'expenses' => ['array', 'max:500'],
            'expenses.*.id' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        $canManage = $user->canManage();
        $result = ['products' => [], 'sales' => [], 'stock_movements' => [], 'expenses' => [], 'errors' => []];

        // 1. Articles créés / modifiés sur le téléphone (gérant ou propriétaire)
        if ($canManage) {
            foreach ($request->input('products', []) as $p) {
                try {
                    $product = Product::withTrashed()->find($p['id']) ?? new Product(['id' => $p['id']]);
                    $product->fill(collect($p)->only(['name', 'category_id', 'price', 'cost', 'sku', 'barcode', 'unit', 'alert_threshold', 'track_stock', 'is_active'])->all());
                    $product->save();
                    $result['products'][] = $product->id;
                } catch (\Throwable $e) {
                    $result['errors'][] = ['type' => 'product', 'id' => $p['id'], 'message' => $e->getMessage()];
                }
            }
        }

        // 2. Ventes faites hors ligne
        foreach ($request->input('sales', []) as $s) {
            try {
                $sale = $sales->create($user, [...$s, 'device_id' => $request->device_id]);
                $result['sales'][] = ['id' => $sale->id, 'number' => $sale->number, 'receipt_url' => $sale->publicUrl()];
            } catch (\Throwable $e) {
                Log::warning('sync sale failed', ['id' => $s['id'], 'e' => $e->getMessage()]);
                $result['errors'][] = ['type' => 'sale', 'id' => $s['id'], 'message' => $e->getMessage()];
            }
        }

        // 3. Entrées / sorties de stock
        if ($canManage) {
            foreach ($request->input('stock_movements', []) as $m) {
                try {
                    if (! StockMovement::whereKey($m['id'])->exists() && ($product = Product::find($m['product_id'] ?? null))) {
                        StockMovement::create([
                            'id' => $m['id'], 'product_id' => $product->id, 'branch_id' => $user->branch_id, 'user_id' => $user->id,
                            'type' => in_array($m['type'] ?? '', ['in', 'out', 'adjust'], true) ? $m['type'] : 'adjust',
                            'quantity' => (float) $m['quantity'], 'reason' => $m['reason'] ?? null,
                        ]);
                        $product->recalculateStock();
                    }
                    $result['stock_movements'][] = $m['id'];
                } catch (\Throwable $e) {
                    $result['errors'][] = ['type' => 'stock', 'id' => $m['id'], 'message' => $e->getMessage()];
                }
            }

            foreach ($request->input('expenses', []) as $x) {
                try {
                    Expense::firstOrCreate(['id' => $x['id']], [
                        'label' => $x['label'], 'category' => $x['category'] ?? 'divers', 'amount' => (int) $x['amount'],
                        'spent_on' => $x['spent_on'] ?? today(), 'branch_id' => $user->branch_id, 'user_id' => $user->id,
                    ]);
                    $result['expenses'][] = $x['id'];
                } catch (\Throwable $e) {
                    $result['errors'][] = ['type' => 'expense', 'id' => $x['id'], 'message' => $e->getMessage()];
                }
            }
        }

        $result['server_time'] = now()->toIso8601String();

        return response()->json($result);
    }
}
