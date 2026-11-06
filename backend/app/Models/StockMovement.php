<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToTenant, HasUuids;

    public const TYPES = [
        'in' => 'Entrée (achat / réception)',
        'out' => 'Sortie (perte, casse, usage)',
        'adjust' => 'Ajustement inventaire',
        'sale' => 'Vente',
        'return' => 'Retour client',
    ];

    protected $fillable = [
        'id', 'tenant_id', 'branch_id', 'product_id', 'user_id', 'type', 'quantity', 'reason', 'sale_id',
        'created_at',
    ];

    protected $casts = ['quantity' => 'float'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
