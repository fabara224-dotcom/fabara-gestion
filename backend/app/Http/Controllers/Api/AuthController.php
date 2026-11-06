<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active || ! $user->tenant_id) {
            return response()->json(['message' => 'E-mail ou mot de passe incorrect.'], 422);
        }

        // Un jeton par appareil (le propriétaire peut déconnecter un téléphone perdu en désactivant le compte)
        $user->tokens()->where('name', $data['device_name'])->delete();
        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json(['token' => $token, ...$this->profile($user)]);
    }

    public function me(Request $request)
    {
        return response()->json($this->profile($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    protected function profile(User $user): array
    {
        $t = $user->tenant;

        return [
            'user' => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
                'role_label' => $user->roleLabel(), 'branch_id' => $user->branch_id, 'branch' => $user->branch?->name,
            ],
            'tenant' => [
                'id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'phone' => $t->phone, 'address' => $t->address,
                'city' => $t->city, 'currency' => $t->currency, 'logo_url' => $t->logoUrl(), 'brand_color' => $t->brand_color,
                'receipt_header' => $t->receipt_header, 'receipt_footer' => $t->receipt_footer, 'tax_id' => $t->tax_id,
                'status' => $t->status, 'days_left' => $t->daysLeft(), 'usable' => $t->isUsable(),
            ],
        ];
    }
}
