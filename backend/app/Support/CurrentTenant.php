<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Entreprise active pour la requête en cours.
 * Renseignée par le middleware SetTenant à partir de l'utilisateur connecté.
 */
class CurrentTenant
{
    protected ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }
}
