<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    public const TYPES = [
        'boutique' => 'Boutique / Prêt-à-porter',
        'alimentation' => 'Alimentation / Supérette',
        'quincaillerie' => 'Quincaillerie',
        'pharmacie' => 'Pharmacie / Parapharmacie',
        'cosmetique' => 'Cosmétique / Beauté',
        'electronique' => 'Téléphones & Électronique',
        'restaurant' => 'Restaurant / Fast-food',
        'imprimerie' => 'Imprimerie / Services',
        'autre' => 'Autre commerce',
    ];

    protected $fillable = [
        'name', 'slug', 'business_type', 'phone', 'email', 'address', 'city', 'country',
        'currency', 'logo_path', 'brand_color', 'receipt_header', 'receipt_footer', 'tax_id',
        'plan_id', 'status', 'trial_ends_at', 'subscription_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function payments(): HasMany { return $this->hasMany(SubscriptionPayment::class); }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        return mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
    }

    /** L'entreprise peut-elle travailler ? (essai en cours ou abonnement payé) */
    public function isUsable(): bool
    {
        return match ($this->status) {
            'active' => ! $this->subscription_ends_at || $this->subscription_ends_at->isFuture(),
            'trial' => $this->trial_ends_at?->isFuture() ?? false,
            'past_due' => true,   // période de grâce : on affiche un avertissement
            default => false,
        };
    }

    public function daysLeft(): ?int
    {
        $end = $this->status === 'trial' ? $this->trial_ends_at : $this->subscription_ends_at;
        return $end ? max(0, (int) now()->diffInDays($end, false)) : null;
    }

    /** Numéro de reçu séquentiel, sans doublon même avec plusieurs caisses en même temps. */
    public function nextSaleNumber(): string
    {
        $n = DB::transaction(function () {
            $row = DB::table('tenants')->where('id', $this->id)->lockForUpdate()->first();
            $next = $row->sale_counter + 1;
            DB::table('tenants')->where('id', $this->id)->update(['sale_counter' => $next]);
            return $next;
        });

        return 'REC-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}
