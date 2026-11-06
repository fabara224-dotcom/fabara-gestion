<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Le propriétaire personnalise son espace : logo, couleur, en-tête et pied des reçus. */
class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'tenant' => app(CurrentTenant::class)->get(),
            'types' => Tenant::TYPES,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $tenant = app(CurrentTenant::class)->get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', Rule::in(array_keys(Tenant::TYPES))],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:80'],
            'tax_id' => ['nullable', 'string', 'max:80'],
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'receipt_header' => ['nullable', 'string', 'max:190'],
            'receipt_footer' => ['nullable', 'string', 'max:190'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) Storage::disk('public')->delete($tenant->logo_path);
            $data['logo_path'] = $request->file('logo')->store('logos/'.$tenant->id, 'public');
        }
        if ($request->boolean('remove_logo') && $tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $data['logo_path'] = null;
        }
        unset($data['logo']);

        $tenant->update($data);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
