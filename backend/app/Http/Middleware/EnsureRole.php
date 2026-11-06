<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Utilisation : ->middleware('role:owner,manager') */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Le super-admin entré dans une entreprise a les droits du propriétaire
        if ($user?->isSuperAdmin() && (in_array('super_admin', $roles, true) || session('impersonate_tenant'))) {
            return $next($request);
        }

        if (! $user || ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès non autorisé pour votre rôle.'], 403);
            }
            abort(403, 'Cette page n\'est pas accessible avec votre rôle.');
        }

        return $next($request);
    }
}
