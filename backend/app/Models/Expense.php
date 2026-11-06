<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    public const CATEGORIES = [
        'loyer' => 'Loyer', 'salaire' => 'Salaires', 'transport' => 'Transport',
        'electricite' => 'Électricité / Eau', 'achat' => 'Achat marchandises',
        'communication' => 'Crédit / Internet', 'divers' => 'Divers',
    ];

    protected $fillable = ['id', 'tenant_id', 'branch_id', 'user_id', 'label', 'category', 'amount', 'spent_on'];

    protected $casts = ['spent_on' => 'date', 'amount' => 'integer'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
