<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre une vente : lignes, total, mouvements de stock, numéro de reçu.
 * Utilisé par la caisse web ET par la synchronisation mobile (ventes faites hors ligne).
 * Idempotent : renvoyer deux fois la même vente (même UUID) ne la crée qu'une fois.
 */
class SaleService
{
    public function create(User $user, array $data): Sale
    {
        $tenant = app(CurrentTenant::class)->get();

        if (! empty($data['id']) && ($existing = Sale::find($data['id']))) {
            return $existing; // déjà synchronisée
        }

        $lines = collect($data['items'] ?? [])->filter(fn ($i) => ($i['quantity'] ?? 0) > 0);
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Le panier est vide.']);
        }

        return DB::transaction(function () use ($user, $data, $lines, $tenant) {
            $products = Product::withTrashed()->whereIn('id', $lines->pluck('product_id')->filter())->get()->keyBy('id');

            $items = $lines->map(function ($l) use ($products) {
                $p = $products[$l['product_id'] ?? ''] ?? null;
                if (! $p && empty($l['name'])) {
                    throw ValidationException::withMessages(['items' => 'Article introuvable.']);
                }
                $qty = (float) $l['quantity'];
                $price = (int) ($l['unit_price'] ?? $p?->price ?? 0);
                return [
                    'id' => $l['id'] ?? (string) Str::uuid(),
                    'product' => $p,
                    'product_id' => $p?->id,
                    'name' => $l['name'] ?? $p->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'unit_cost' => (int) ($p?->cost ?? 0),
                    'total' => (int) round($qty * $price),
                ];
            });

            $subtotal = (int) $items->sum('total');
            $discount = min($subtotal, max(0, (int) ($data['discount'] ?? 0)));
            $total = $subtotal - $discount;
            $method = $data['payment_method'] ?? 'cash';
            $paid = $method === 'credit' ? (int) ($data['paid'] ?? 0) : (int) ($data['paid'] ?? $total);

            // Numéro : celui du téléphone (hors ligne, préfixé par l'appareil) s'il est libre, sinon séquence serveur
            $number = $data['number'] ?? null;
            if (! $number || Sale::where('number', $number)->exists()) {
                $number = $tenant->nextSaleNumber();
            }

            $customerId = $this->resolveCustomer($data);

            $sale = Sale::create([
                'id' => $data['id'] ?? (string) Str::uuid(),
                'branch_id' => $data['branch_id'] ?? $user->branch_id,
                'user_id' => $user->isSuperAdmin() ? null : $user->id,
                'customer_id' => $customerId,
                'number' => $number,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'payment_method' => $method,
                'receipt_token' => Str::random(32),
                'device_id' => $data['device_id'] ?? 'web',
                'sold_at' => isset($data['sold_at']) ? Carbon::parse($data['sold_at']) : now(),
            ]);

            foreach ($items as $it) {
                $sale->items()->create([
                    'id' => $it['id'], 'tenant_id' => $sale->tenant_id, 'product_id' => $it['product_id'],
                    'name' => $it['name'], 'quantity' => $it['quantity'], 'unit_price' => $it['unit_price'],
                    'unit_cost' => $it['unit_cost'], 'total' => $it['total'],
                ]);

                if ($it['product']?->track_stock) {
                    StockMovement::create([
                        'branch_id' => $sale->branch_id, 'product_id' => $it['product_id'], 'user_id' => $sale->user_id,
                        'type' => 'sale', 'quantity' => -$it['quantity'], 'reason' => $sale->number, 'sale_id' => $sale->id,
                    ]);
                    $it['product']->decrement('stock', $it['quantity']);
                }
            }

            if ($customerId && $total > $paid) {
                Customer::whereKey($customerId)->increment('balance', $total - $paid);
            }

            return $sale->load('items');
        });
    }

    /** Annule une vente : remet les articles en stock. */
    public function cancel(Sale $sale, User $user): Sale
    {
        if ($sale->status === 'cancelled') return $sale;

        return DB::transaction(function () use ($sale, $user) {
            foreach ($sale->items as $item) {
                if ($item->product_id && $item->product?->track_stock) {
                    StockMovement::create([
                        'branch_id' => $sale->branch_id, 'product_id' => $item->product_id, 'user_id' => $user->id,
                        'type' => 'return', 'quantity' => $item->quantity, 'reason' => 'Annulation '.$sale->number,
                        'sale_id' => $sale->id,
                    ]);
                    $item->product->increment('stock', $item->quantity);
                }
            }
            if ($sale->customer_id && $sale->due() > 0) {
                Customer::whereKey($sale->customer_id)->decrement('balance', $sale->due());
            }
            $sale->update(['status' => 'cancelled']);
            return $sale;
        });
    }

    protected function resolveCustomer(array $data): ?string
    {
        if (! empty($data['customer_id'])) return $data['customer_id'];
        $phone = trim((string) ($data['customer_phone'] ?? ''));
        if ($phone === '') return null;

        return Customer::firstOrCreate(['phone' => $phone], ['name' => $data['customer_name'] ?: 'Client '.$phone])->id;
    }
}
