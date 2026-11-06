<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Active l'entreprise de l'utilisateur connecté pour toute la requête.
 * Le super-admin peut "entrer" dans une entreprise (support client) via la session.
 */
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            abort(403, 'Votre compte a été désactivé par votre entreprise.');
        }

        $tenant = null;
        if ($user->isSuperAdmin()) {
            if ($request->hasSession() && ($id = $request->session()->get('impersonate_tenant'))) {
                $tenant = Tenant::find($id);
            }
        } else {
            $tenant = $user->tenant;
            if (! $tenant) {
                abort(403, 'Aucune entreprise liée à ce compte.');
            }
        }

        app(CurrentTenant::class)->set($tenant);
        view()->share('currentTenant', $tenant);

        // "Vu pour la dernière fois" (utile au propriétaire), au plus une fois toutes les 5 min
        if (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(5))) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
