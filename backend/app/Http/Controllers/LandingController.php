<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing', [
            'plans' => Plan::where('is_active', true)->orderBy('price_gnf')->get(),
            'types' => Tenant::TYPES,
        ]);
    }
}
