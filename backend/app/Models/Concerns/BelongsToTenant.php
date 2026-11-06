<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Isolation des données : toute requête sur un modèle qui utilise ce trait
 * est automatiquement filtrée sur l'entreprise de l'utilisateur connecté,
 * et tout nouvel enregistrement reçoit son tenant_id.
 *
 * Une entreprise ne peut donc jamais lire ni modifier les données d'une autre,
 * même si un identifiant est deviné dans une URL.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenantId = app(CurrentTenant::class)->id();
            if ($tenantId !== null) {
                $query->where($query->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = app(CurrentTenant::class)->id();
            }
            if (empty($model->tenant_id)) {
                throw new \RuntimeException('Aucune entreprise active : enregistrement refusé.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
