<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Inscription d'une nouvelle entreprise : essai gratuit, sans carte bancaire. */
class RegisterController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.register', [
            'types' => Tenant::TYPES,
            'plans' => Plan::where('is_active', true)->orderBy('price_gnf')->get(),
            'selectedPlan' => $request->query('formule', 'pro'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'business_type' => ['required', Rule::in(array_keys(Tenant::TYPES))],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['nullable', 'exists:plans,code'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $slug = Str::slug($data['business_name']);
            $base = $slug ?: 'entreprise';
            $i = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $base.'-'.(++$i);
            }

            $tenant = Tenant::create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'business_type' => $data['business_type'],
                'city' => $data['city'] ?: 'Conakry',
                'phone' => $data['phone'],
                'email' => $data['email'],
                'plan_id' => Plan::where('code', $data['plan'] ?? 'pro')->value('id'),
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(config('saas.trial_days')),
            ]);

            app(CurrentTenant::class)->set($tenant);
            $branch = Branch::create(['name' => 'Boutique principale', 'phone' => $data['phone']]);

            return User::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'owner',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Bienvenue ! Votre essai gratuit de '.config('saas.trial_days').' jours commence. Ajoutez vos premiers articles.');
    }
}
