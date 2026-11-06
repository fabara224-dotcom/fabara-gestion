<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Rôles :
 *  - super_admin : toi, propriétaire de la plateforme (toutes les entreprises, abonnements)
 *  - owner       : propriétaire d'une entreprise cliente — voit tout SON entreprise (toutes boutiques),
 *                  ventes de chaque vendeur, bénéfices, logo et paramètres
 *  - manager     : gérant(e) d'une boutique — caisse, stock, dépenses de sa boutique
 *  - cashier     : vendeur(se) — caisse et consultation du stock
 *
 * Les utilisateurs ne sont PAS filtrés par le scope global (connexion, super-admin),
 * on filtre explicitement par tenant_id dans les contrôleurs.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = [
        'owner' => 'Propriétaire',
        'manager' => 'Gérant(e)',
        'cashier' => 'Vendeur(se)',
    ];

    protected $fillable = [
        'tenant_id', 'branch_id', 'name', 'email', 'phone', 'password', 'role', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isOwner(): bool { return $this->role === 'owner'; }
    public function isManager(): bool { return $this->role === 'manager'; }

    /** Peut gérer le stock, les dépenses, les produits */
    public function canManage(): bool { return in_array($this->role, ['owner', 'manager'], true); }

    /** Limite les données à sa boutique (sauf propriétaire qui voit toutes ses boutiques) */
    public function scopedBranchId(): ?int
    {
        return $this->isOwner() ? null : $this->branch_id;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ($this->isSuperAdmin() ? 'Super-admin' : $this->role);
    }

    public function initials(): string
    {
        $w = preg_split('/\s+/', trim($this->name));
        return mb_strtoupper(mb_substr($w[0] ?? '', 0, 1).mb_substr($w[1] ?? '', 0, 1));
    }
}
