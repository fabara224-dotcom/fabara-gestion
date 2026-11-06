<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloque l'accès si l'essai est terminé ou si la location n'est pas payée. */
class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(CurrentTenant::class)->get();

        // Sans entreprise active, aucune page métier n'est accessible (évite toute requête non filtrée)
        if (! $tenant) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Aucune entreprise active.'], 403);
            }
            return $request->user()?->isSuperAdmin() ? redirect()->route('admin.tenants.index') : abort(403);
        }

        if (! $tenant->isUsable()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Abonnement expiré. Contactez votre fournisseur pour réactiver votre compte.',
                    'code' => 'subscription_expired',
                ], 402);
            }
            return redirect()->route('billing');
        }

        return $next($request);
    }
}
