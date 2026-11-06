<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function store(Request $request)
    {
        $tenant = app(CurrentTenant::class)->get();
        if ($tenant->plan && Branch::count() >= $tenant->plan->max_branches) {
            return back()->withErrors(['branch' => 'Votre formule permet '.$tenant->plan->max_branches.' boutique(s).']);
        }

        Branch::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]));

        return back()->with('success', 'Boutique ajoutée.');
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]));

        return back()->with('success', 'Boutique mise à jour.');
    }
}
