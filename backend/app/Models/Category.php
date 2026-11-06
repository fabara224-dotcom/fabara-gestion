<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    protected $fillable = ['id', 'tenant_id', 'name', 'color'];

    public function products(): HasMany { return $this->hasMany(Product::class); }
}
