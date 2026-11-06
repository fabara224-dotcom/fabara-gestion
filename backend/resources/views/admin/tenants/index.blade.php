@extends('layouts.app')
@section('title', 'Entreprises clientes')
@section('content')
<div class="grid g-4 mb-3">
  <div class="card stat"><div class="ico bg-blue"><x-icon name="store"/></div><div class="lbl">Entreprises</div><div class="val">{{ number_format($stats['total'], 0, ',', ' ') }}</div><div class="sub">+{{ $stats['new_month'] }} ce mois</div></div>
  <div class="card stat"><div class="ico bg-green"><x-icon name="check"/></div><div class="lbl">Abonnés actifs</div><div class="val t-green">{{ $stats['active'] }}</div><div class="sub">{{ $stats['trial'] }} en essai</div></div>
  <div class="card stat"><div class="ico bg-red"><x-icon name="money"/></div><div class="lbl">Revenu mensuel récurrent</div><div class="val">@money($stats['mrr'])</div><div class="sub">encaissé ce mois : @money($stats['month_payments'])</div></div>
  <a href="{{ route('admin.tenants.index', ['statut' => 'past_due']) }}" class="card stat"><div class="ico bg-yellow"><x-icon name="alert"/></div><div class="lbl">En retard / suspendus</div><div class="val t-orange">{{ $stats['late'] }}</div></a>
</div>

<form class="row wrap mb-2" method="GET" style="gap:10px">
  <div class="search grow" style="min-width:220px"><x-icon name="search"/><input class="input" name="q" value="{{ request('q') }}" placeholder="Nom, téléphone, code"></div>
  @foreach(['' => 'Toutes', 'trial' => 'Essai', 'active' => 'Actives', 'past_due' => 'En retard', 'suspended' => 'Suspendues'] as $k => $v)
    <a href="{{ route('admin.tenants.index', array_filter(['statut' => $k, 'q' => request('q')])) }}" class="chip {{ request('statut', '') === $k ? 'on' : '' }}">{{ $v }}</a>
  @endforeach
</form>

<div class="card p0"><div class="table-wrap"><table class="table">
  <thead><tr><th>Entreprise</th><th>Type</th><th>Formule</th><th class="right">Utilisateurs</th><th>Statut</th><th>Échéance</th><th></th></tr></thead>
  <tbody>
  @foreach($tenants as $t)
    @php $st = ['trial' => ['Essai', 'b-blue'], 'active' => ['Actif', 'b-green'], 'past_due' => ['En retard', 'b-yellow'], 'suspended' => ['Suspendu', 'b-red']][$t->status] ?? [$t->status, 'b-gray']; @endphp
    <tr>
      <td><a href="{{ route('admin.tenants.show', $t) }}" class="row"><div class="logo-tile" style="width:36px;height:36px;font-size:13px;background:{{ $t->brand_color }}">@if($t->logoUrl())<img src="{{ $t->logoUrl() }}">@else{{ $t->initials() }}@endif</div><div><div class="bold">{{ $t->name }}</div><div class="xs muted">{{ $t->slug }} · {{ $t->phone }} · {{ $t->city }}</div></div></a></td>
      <td class="small">{{ \App\Models\Tenant::TYPES[$t->business_type] ?? $t->business_type }}</td>
      <td class="small">{{ $t->plan->name ?? '—' }}</td>
      <td class="right">{{ $t->users_count }}</td>
      <td><span class="badge {{ $st[1] }}">{{ $st[0] }}</span></td>
      <td class="small">{{ ($t->status === 'trial' ? $t->trial_ends_at : $t->subscription_ends_at)?->format('d/m/Y') ?? '—' }}</td>
      <td class="right"><a href="{{ route('admin.tenants.show', $t) }}" class="btn btn-light btn-sm">Gérer</a></td>
    </tr>
  @endforeach
  </tbody>
</table></div></div>
{{ $tenants->links() }}
@endsection
