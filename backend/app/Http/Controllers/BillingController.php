<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Support\CurrentTenant;

class BillingController extends Controller
{
    public function show()
    {
        $tenant = app(CurrentTenant::class)->get();
        abort_unless($tenant, 404);

        return view('billing', [
            'tenant' => $tenant->load(['plan', 'payments.plan']),
            'plans' => Plan::where('is_active', true)->orderBy('price_gnf')->get(),
        ]);
    }
}
