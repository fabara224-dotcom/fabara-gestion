<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\ReceiptService;
use App\Support\CurrentTenant;

/**
 * Reçu consultable par l'acheteur via un lien secret (32 caractères aléatoires).
 * Le lien ne donne accès qu'à CE reçu, rien d'autre de l'entreprise.
 */
class PublicReceiptController extends Controller
{
    protected function find(string $token): Sale
    {
        $sale = Sale::withoutGlobalScope('tenant')->where('receipt_token', $token)->firstOrFail();
        app(CurrentTenant::class)->set($sale->tenant);
        view()->share('currentTenant', $sale->tenant);

        return $sale->load(['items', 'tenant', 'branch', 'user']);
    }

    public function show(string $token)
    {
        return view('receipts.public', ['sale' => $this->find($token)]);
    }

    public function pdf(string $token, ReceiptService $receipts)
    {
        $sale = $this->find($token);

        return $receipts->pdf($sale, request('format', 'ticket'))->download($receipts->filename($sale));
    }
}
