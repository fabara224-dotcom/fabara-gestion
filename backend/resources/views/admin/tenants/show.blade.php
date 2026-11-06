@extends('layouts.app')
@section('title', $tenant->name)
@section('actions')
  <form method="POST" action="{{ route('admin.tenants.impersonate', $tenant) }}">@csrf<button class="btn btn-dark"><x-icon name="eye"/> Entrer dans l'espace (support)</button></form>
@endsection
@section('content')
<a href="{{ route('admin.tenants.index') }}" class="btn btn-ghost btn-sm mb-2"><x-icon name="arrow-left"/> Toutes les entreprises</a>
<div class="grid g-4 mb-3">
  <div class="card stat"><div class="lbl">Articles</div><div class="val">{{ $usage['products'] }}</div></div>
  <div class="card stat"><div class="lbl">Ventes ce mois</div><div class="val">{{ $usage['sales_month'] }}</div></div>
  <div class="card stat"><div class="lbl">Dernière vente</div><div class="val" style="font-size:18px">{{ $usage['last_sale'] ? \Illuminate\Support\Carbon::parse($usage['last_sale'])->diffForHumans() : 'jamais' }}</div></div>
  <div class="card stat"><div class="lbl">Inscrite le</div><div class="val" style="font-size:18px">{{ $tenant->created_at->format('d/m/Y') }}</div></div>
</div>

<div class="grid g-main" style="align-items:start">
  <div class="grid" style="gap:18px">
    <div class="card">
      <h3 class="bold mb-2">Abonnement</h3>
      <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="grid g-2" style="gap:12px">@csrf @method('PUT')
        <div><label class="lbl">Statut</label><select class="input" name="status">@foreach(['trial' => 'Essai', 'active' => 'Actif', 'past_due' => 'En retard (grâce)', 'suspended' => 'Suspendu (bloqué)'] as $k => $v)<option value="{{ $k }}" @selected($tenant->status === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label class="lbl">Formule</label><select class="input" name="plan_id">@foreach($plans as $p)<option value="{{ $p->id }}" @selected($tenant->plan_id === $p->id)>{{ $p->name }} — {{ number_format($p->price_gnf, 0, ',', ' ') }} GNF</option>@endforeach</select></div>
        <div><label class="lbl">Fin d'essai</label><input class="input" type="date" name="trial_ends_at" value="{{ $tenant->trial_ends_at?->toDateString() }}"></div>
        <div><label class="lbl">Fin d'abonnement</label><input class="input" type="date" name="subscription_ends_at" value="{{ $tenant->subscription_ends_at?->toDateString() }}"></div>
        <div><button class="btn btn-primary">Enregistrer</button></div>
      </form>
    </div>
    <div class="card p0"><div class="card-h" style="padding:18px 20px 0"><h3>Utilisateurs</h3></div><table class="table">
      @foreach($tenant->users as $u)<tr><td><b>{{ $u->name }}</b><div class="xs muted">{{ $u->email }}</div></td><td><span class="badge b-gray">{{ $u->roleLabel() }}</span></td><td class="small muted">{{ $u->last_seen_at?->diffForHumans() ?? 'jamais' }}</td></tr>@endforeach
    </table></div>
  </div>
  <div class="card">
    <h3 class="bold mb-2">Enregistrer un paiement</h3>
    <form method="POST" action="{{ route('admin.tenants.payment', $tenant) }}">@csrf
      <div class="field"><label class="lbl">Formule</label><select class="input" name="plan_id">@foreach($plans as $p)<option value="{{ $p->id }}" @selected($tenant->plan_id === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
      <div class="grid g-2" style="gap:10px">
        <div class="field"><label class="lbl">Mois</label><input class="input" type="number" name="months" value="1" min="1" max="24"></div>
        <div class="field"><label class="lbl">Moyen</label><select class="input" name="method"><option value="orange_money">Orange Money</option><option value="mtn_momo">MTN MoMo</option><option value="cash">Espèces</option><option value="virement">Virement</option></select></div>
      </div>
      <div class="field"><label class="lbl">Référence de transaction</label><input class="input" name="reference"></div>
      <button class="btn btn-primary btn-block">Valider et activer</button>
    </form>
    @if($tenant->payments->count())
      <div class="lbl mt-3">Historique</div>
      @foreach($tenant->payments->sortByDesc('paid_at') as $pay)<div class="row between small mt-1"><span>{{ $pay->paid_at->format('d/m/Y') }} · {{ $pay->plan->name ?? '' }} · {{ $pay->months }} mois</span><b>{{ number_format($pay->amount, 0, ',', ' ') }} GNF</b></div>@endforeach
    @endif
    <div class="lbl mt-3">Contact</div>
    <div class="small">{{ $tenant->phone }} · {{ $tenant->email }}<br>{{ $tenant->address }} {{ $tenant->city }}</div>
  </div>
</div>
@endsection
