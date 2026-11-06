<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Le propriétaire crée les comptes de ses gérants et vendeurs. */
class TeamController extends Controller
{
    public function index()
    {
        $tenant = app(CurrentTenant::class)->get();

        return view('team.index', [
            'users' => User::where('tenant_id', $tenant->id)->with('branch')->orderByRaw("role = 'owner' desc")->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'roles' => User::ROLES,
            'limit' => $tenant->plan?->max_users,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = app(CurrentTenant::class)->get();
        if ($tenant->plan && User::where('tenant_id', $tenant->id)->count() >= $tenant->plan->max_users) {
            return back()->withErrors(['email' => 'Votre formule permet '.$tenant->plan->max_users.' utilisateurs. Passez à la formule supérieure.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['manager', 'cashier'])],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'password' => ['required', Password::min(6)],
        ]);

        User::create([...$data, 'tenant_id' => $tenant->id]);

        return back()->with('success', 'Compte de '.$data['name'].' créé. Donnez-lui son e-mail et son mot de passe.');
    }

    public function update(Request $request, User $user)
    {
        $this->own($user);
        $tenantId = app(CurrentTenant::class)->id();

        $data = $request->validate([
            'role' => ['sometimes', Rule::in(['manager', 'cashier'])],
            'branch_id' => ['sometimes', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', Password::min(6)],
        ]);
        if (empty($data['password'])) unset($data['password']);
        if ($user->isOwner()) unset($data['role'], $data['is_active']); // on ne se bloque pas soi-même

        $user->update($data);
        if (isset($data['is_active']) && ! $data['is_active']) {
            $user->tokens()->delete(); // déconnecte aussi l'application mobile
        }

        return back()->with('success', 'Compte de '.$user->name.' mis à jour.');
    }

    public function destroy(User $user)
    {
        $this->own($user);
        abort_if($user->isOwner(), 403, 'Le compte propriétaire ne peut pas être supprimé.');
        $user->tokens()->delete();
        $user->delete();

        return back()->with('success', 'Compte supprimé. Ses ventes restent dans l\'historique.');
    }

    /** Un propriétaire ne peut toucher qu'aux comptes de SON entreprise */
    protected function own(User $user): void
    {
        abort_unless($user->tenant_id === app(CurrentTenant::class)->id(), 404);
    }
}
