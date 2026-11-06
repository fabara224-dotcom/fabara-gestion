<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'id', 'sale_id', 'tenant_id', 'product_id', 'name', 'quantity', 'unit_price', 'unit_cost', 'total',
    ];

    protected $casts = ['quantity' => 'float', 'unit_price' => 'integer', 'total' => 'integer', 'unit_cost' => 'integer'];

    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
}
