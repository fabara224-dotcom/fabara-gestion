@extends('layouts.app')
@section('title', 'Équipe')
@section('content')
<div class="grid g-main" style="align-items:start">
  <div class="card p0">
    <div class="card-h" style="padding:18px 20px 0"><h3>Comptes de votre entreprise</h3>@if($limit)<span class="badge b-gray">{{ $users->count() }} / {{ $limit }} utilisateurs</span>@endif</div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Nom</th><th>Rôle</th><th>Boutique</th><th>Dernière activité</th><th></th></tr></thead>
      <tbody>
      @foreach($users as $m)
        <tr x-data="{e:false}">
          <td><div class="row"><div class="avatar" style="width:36px;height:36px;font-size:13px">{{ $m->initials() }}</div><div><div class="bold">{{ $m->name }}</div><div class="xs muted">{{ $m->email }} {{ $m->phone ? '· '.$m->phone : '' }}</div></div></div></td>
          <td><span class="badge {{ $m->isOwner() ? 'b-red' : ($m->isManager() ? 'b-blue' : 'b-gray') }}">{{ $m->roleLabel() }}</span> @unless($m->is_active)<span class="badge b-gray">Désactivé</span>@endunless</td>
          <td class="small">{{ $m->isOwner() ? 'Toutes' : ($m->branch->name ?? '—') }}</td>
          <td class="small muted">{{ $m->last_seen_at?->diffForHumans() ?? 'jamais' }}</td>
          <td class="right">@unless($m->isOwner())<button class="btn btn-light btn-sm" @click="e=!e"><x-icon name="edit"/></button>@endunless</td>
        </tr>
        @unless($m->isOwner())
        <tr x-show="e" x-cloak><td colspan="5" style="background:var(--bg)">
          <form method="POST" action="{{ route('team.update', $m) }}" class="row wrap" style="gap:8px">@csrf @method('PUT')
            <select class="input sm" name="role" style="width:auto">@foreach(['manager' => 'Gérant(e)', 'cashier' => 'Vendeur(se)'] as $k => $v)<option value="{{ $k }}" @selected($m->role === $k)>{{ $v }}</option>@endforeach</select>
            <select class="input sm" name="branch_id" style="width:auto">@foreach($branches as $b)<option value="{{ $b->id }}" @selected($m->branch_id === $b->id)>{{ $b->name }}</option>@endforeach</select>
            <input class="input sm" name="password" type="text" placeholder="Nouveau mot de passe" style="width:190px">
            <select class="input sm" name="is_active" style="width:auto"><option value="1" @selected($m->is_active)>Actif</option><option value="0" @selected(!$m->is_active)>Désactivé (bloque l'accès)</option></select>
            <button class="btn btn-primary btn-sm">Enregistrer</button>
          </form>
          <form method="POST" action="{{ route('team.destroy', $m) }}" class="mt-1" onsubmit="return confirm('Supprimer ce compte ?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm t-red">Supprimer le compte</button></form>
        </td></tr>
        @endunless
      @endforeach
      </tbody>
    </table></div>
  </div>

  <div class="card" style="position:sticky;top:90px">
    <h3 class="bold" style="font-size:17px">Ajouter un vendeur</h3>
    <p class="small muted mb-3">Il se connectera avec cet e-mail sur le site ou l'application. Il ne verra que sa boutique.</p>
    <form method="POST" action="{{ route('team.store') }}">@csrf
      <div class="field"><label class="lbl">Nom complet</label><input class="input" name="name" required value="{{ old('name') }}"></div>
      <div class="field"><label class="lbl">E-mail de connexion</label><input class="input" type="email" name="email" required value="{{ old('email') }}"></div>
      <div class="field"><label class="lbl">Téléphone</label><input class="input" name="phone" value="{{ old('phone') }}"></div>
      <div class="grid g-2" style="gap:10px">
        <div class="field"><label class="lbl">Rôle</label><select class="input" name="role"><option value="manager">Gérant(e)</option><option value="cashier">Vendeur(se)</option></select></div>
        <div class="field"><label class="lbl">Boutique</label><select class="input" name="branch_id">@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
      </div>
      <div class="field"><label class="lbl">Mot de passe</label><input class="input" name="password" required minlength="6" value="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4)).rand(1000, 9999) }}"><div class="hint">Notez-le et donnez-le à la personne.</div></div>
      <button class="btn btn-primary btn-block btn-lg"><x-icon name="plus"/> Créer le compte</button>
    </form>
    <div class="alert alert-warn mt-3 small" style="margin-bottom:0"><div><b>Gérant(e)</b> : caisse, stock, dépenses, rapports de sa boutique.<br><b>Vendeur(se)</b> : caisse et ses propres ventes uniquement.</div></div>
  </div>
</div>
@endsection
