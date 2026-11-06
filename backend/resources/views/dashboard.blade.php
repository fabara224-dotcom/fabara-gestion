@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('actions')
  <a href="{{ route('pos') }}" class="btn btn-primary"><x-icon name="cart"/> <span>Nouvelle vente</span></a>
@endsection
@section('content')
@php $u = auth()->user(); $owner = $u->isOwner() || $u->isSuperAdmin(); $max = max(1, $chart->max('value')); @endphp

<div class="row between wrap mb-3">
  <div>
    <div style="font-size:22px;font-weight:800;letter-spacing:-.02em">Bonjour {{ explode(' ', $u->name)[0] }} 👋</div>
    <div class="muted">{{ now()->translatedFormat('l d F Y') }} · {{ $owner ? 'Vue d\'ensemble de votre entreprise' : ($u->branch->name ?? '') }}</div>
  </div>
</div>

<div class="grid g-4">
  <div class="card stat"><div class="ico bg-red"><x-icon name="money"/></div>
    <div class="lbl">Ventes aujourd'hui</div><div class="val">@money($stats['today_total'])</div><div class="sub">{{ $stats['today_count'] }} reçu(s)</div></div>
  <div class="card stat"><div class="ico bg-blue"><x-icon name="trend"/></div>
    <div class="lbl">Ce mois-ci</div><div class="val">@money($stats['month_total'])</div><div class="sub">{{ $stats['month_count'] }} ventes</div></div>
  <div class="card stat"><div class="ico bg-green"><x-icon name="wallet"/></div>
    <div class="lbl">Bénéfice brut du mois</div><div class="val t-green">@money($stats['month_profit'])</div><div class="sub">ventes − coût d'achat</div></div>
  <a href="{{ route('products.index', ['stock' => 'low']) }}" class="card stat"><div class="ico bg-yellow"><x-icon name="alert"/></div>
    <div class="lbl">Stock faible</div><div class="val {{ $stats['low_stock'] ? 't-orange' : '' }}">{{ $stats['low_stock'] }}</div><div class="sub">sur {{ $stats['products'] }} articles</div></a>
</div>

<div class="grid g-main mt-3">
  <div class="card">
    <div class="card-h"><h3>Ventes des 14 derniers jours</h3><span class="badge b-gray">{{ $currentTenant->currency }}</span></div>
    <div class="bars">
      @foreach($chart as $d)
        <div class="b" title="{{ \App\Support\Money::format($d['value']) }}">
          <small class="bold" style="color:var(--ink)">{{ $d['value'] ? \App\Support\Money::short($d['value']) : '' }}</small>
          <span style="height:{{ max(2, $d['value'] / $max * 82) }}%"></span>
          <small>{{ $d['label'] }}</small>
        </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h3>Meilleures ventes du mois</h3></div>
    @php $topMax = max(1, $topProducts->max('amount')); @endphp
    @forelse($topProducts as $p)
      <div class="mt-2">
        <div class="row between small"><span class="bold">{{ $p->name }}</span><span class="muted">@qty($p->qty) vendus</span></div>
        <div class="progress mt-1"><span style="width:{{ $p->amount / $topMax * 100 }}%"></span></div>
      </div>
    @empty
      <div class="empty"><div class="ill"><x-icon name="chart"/></div>Les articles les plus vendus s'afficheront ici.</div>
    @endforelse
  </div>
</div>

@if($owner)
<div class="grid g-main mt-3">
  <div class="card p0">
    <div class="card-h" style="padding:18px 20px 0"><h3><x-icon name="eye" width="18" height="18" style="vertical-align:-3px"/> Votre équipe aujourd'hui</h3><a href="{{ route('team.index') }}" class="btn btn-light btn-sm">Gérer</a></div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Vendeur</th><th>Boutique</th><th>Dernière activité</th><th class="right">Reçus</th><th class="right">Encaissé</th></tr></thead>
      <tbody>
      @forelse($team as $m)
        <tr>
          <td><div class="row"><div class="avatar" style="width:34px;height:34px;font-size:12px">{{ $m->initials() }}</div><div><div class="bold">{{ $m->name }}</div><div class="xs muted">{{ $m->roleLabel() }} @if(!$m->is_active)<span class="badge b-gray">désactivé</span>@endif</div></div></div></td>
          <td class="muted">{{ $m->branch->name ?? '—' }}</td>
          <td class="muted small">{{ $m->last_seen_at?->diffForHumans() ?? 'jamais' }}</td>
          <td class="right">{{ $m->today_count }}</td>
          <td class="right bold">@money($m->today_total)</td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty">Ajoutez votre gérant(e) ou vos vendeurs pour suivre leurs ventes ici.<div class="mt-2"><a href="{{ route('team.index') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/> Ajouter un vendeur</a></div></div></td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div>

  <div class="card">
    <div class="card-h"><h3>Encaissements du mois</h3></div>
    @php $pm = \App\Models\Sale::PAYMENT_METHODS; $ptot = max(1, $payments->sum()); @endphp
    @forelse($payments as $k => $v)
      <div class="row between mt-2 small"><span class="bold">{{ $pm[$k] ?? $k }}</span><span>@money($v)</span></div>
      <div class="progress mt-1"><span style="width:{{ $v / $ptot * 100 }}%;background:var(--brand)"></span></div>
    @empty
      <div class="muted small">Aucun encaissement ce mois-ci.</div>
    @endforelse
    @if($branches->count() > 1)
      <div class="card-h mt-3"><h3>Par boutique aujourd'hui</h3></div>
      @foreach($branches as $b)<div class="row between small mt-1"><span>{{ $b->name }}</span><b>@money($b->today_total)</b></div>@endforeach
    @endif
  </div>
</div>
@endif

<div class="grid g-main mt-3">
  <div class="card p0">
    <div class="card-h" style="padding:18px 20px 0"><h3>Dernières ventes</h3><a href="{{ route('sales.index') }}" class="btn btn-light btn-sm">Tout voir</a></div>
    <div class="table-wrap"><table class="table">
      <tbody>
      @forelse($recent as $s)
        <tr onclick="location='{{ route('sales.show', $s) }}'" style="cursor:pointer">
          <td><div class="bold">{{ $s->number }}</div><div class="xs muted">{{ $s->sold_at->format('d/m H:i') }} · {{ $s->user->name ?? '—' }}</div></td>
          <td class="muted small">{{ $s->customer_name ?: 'Client comptoir' }}</td>
          <td><span class="badge b-gray">{{ $s->paymentLabel() }}</span></td>
          <td class="right bold">@money($s->total)</td>
        </tr>
      @empty
        <tr><td><div class="empty"><div class="ill"><x-icon name="receipt"/></div>Aucune vente pour l'instant.<div class="mt-2"><a href="{{ route('pos') }}" class="btn btn-primary btn-sm">Ouvrir la caisse</a></div></div></td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div>

  <div class="card">
    <div class="card-h"><h3>À réapprovisionner</h3>@if($u->canManage() || $u->isSuperAdmin())<a href="{{ route('stock.index') }}" class="btn btn-light btn-sm">Stock</a>@endif</div>
    @forelse($lowProducts as $p)
      <div class="row mt-2">
        <div class="thumb">@if($p->image_url)<img src="{{ $p->image_url }}" alt="">@else 📦 @endif</div>
        <div class="grow"><div class="bold small">{{ $p->name }}</div><div class="xs muted">seuil : @qty($p->alert_threshold)</div></div>
        <span class="badge {{ $p->stock <= 0 ? 'b-red' : 'b-yellow' }}">@qty($p->stock) {{ $p->unit }}</span>
      </div>
    @empty
      <div class="empty" style="padding:30px 10px"><div class="ill"><x-icon name="check"/></div>Tout le stock est au-dessus du seuil.</div>
    @endforelse
  </div>
</div>
@endsection
