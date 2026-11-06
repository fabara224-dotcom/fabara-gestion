<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Point de vente (une entreprise peut avoir plusieurs boutiques). */
class Branch extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'address', 'phone', 'is_active'];

    public function users(): HasMany { return $this->hasMany(User::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
