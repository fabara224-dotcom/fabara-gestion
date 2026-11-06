<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'id', 'tenant_id', 'category_id', 'name', 'sku', 'barcode', 'description', 'price', 'cost',
        'unit', 'alert_threshold', 'image_path', 'gallery', 'track_stock', 'is_active',
    ];

    protected $casts = [
        'gallery' => 'array',
        'stock' => 'float',
        'alert_threshold' => 'float',
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'integer',
        'cost' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function movements(): HasMany { return $this->hasMany(StockMovement::class); }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function isLow(): bool
    {
        return $this->track_stock && $this->stock <= $this->alert_threshold;
    }

    /** Le stock affiché = somme des mouvements (fiable même avec des ventes hors ligne). */
    public function recalculateStock(): void
    {
        $this->stock = (float) StockMovement::where('product_id', $this->id)->sum('quantity');
        $this->saveQuietly();
    }

    public function margin(): int
    {
        return $this->price - $this->cost;
    }
}
