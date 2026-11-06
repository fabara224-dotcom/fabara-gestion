@extends('layouts.app')
@section('title', 'Rapports')
@section('content')
@php $pm = \App\Models\Sale::PAYMENT_METHODS; $maxDay = max(1, $byDay->max()); @endphp
<form class="row wrap mb-3" method="GET" style="gap:8px" x-data="{p:'{{ $period }}'}">
  @foreach(['jour' => "Aujourd'hui", 'semaine' => 'Cette semaine', 'mois' => 'Ce mois', 'annee' => 'Cette année', 'perso' => 'Période…'] as $k => $v)
    <label class="chip" :class="p==='{{ $k }}' && 'on'"><input type="radio" name="periode" value="{{ $k }}" x-model="p" hidden @change="p!=='perso' && $el.form.submit()">{{ $v }}</label>
  @endforeach
  <template x-if="p==='perso'"><div class="row" style="gap:6px">
    <input class="input sm" type="date" name="du" value="{{ $from->toDateString() }}"><input class="input sm" type="date" name="au" value="{{ $to->toDateString() }}"><button class="btn btn-dark btn-sm">Voir</button></div></template>
</form>
<div class="muted small mb-2">Du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}</div>

<div class="grid g-4">
  <div class="card stat"><div class="lbl">Chiffre d'affaires</div><div class="val">@money($revenue)</div><div class="sub">{{ $count }} ventes · panier moyen @money($average)</div></div>
  <div class="card stat"><div class="lbl">Coût des articles vendus</div><div class="val">@money($cost)</div><div class="sub">remises accordées : @money($discounts)</div></div>
  <div class="card stat"><div class="lbl">Dépenses</div><div class="val t-orange">@money($expenses)</div></div>
  <div class="card stat" style="background:{{ $net >= 0 ? '#e3f6f3' : 'var(--brand-soft)' }}"><div class="lbl">Bénéfice net</div><div class="val {{ $net >= 0 ? 't-green' : 't-red' }}">@money($net)</div><div class="sub">brut @money($gross) − dépenses</div></div>
</div>

<div class="grid g-main mt-3">
  <div class="card"><div class="card-h"><h3>Ventes par jour</h3></div>
    @if($byDay->isEmpty())<div class="empty">Aucune vente sur cette période.</div>@else
    <div class="bars">@foreach($byDay->take(-31) as $d => $v)
      <div class="b" title="{{ \App\Support\Money::format($v) }}"><span style="height:{{ max(2, $v / $maxDay * 85) }}%"></span><small>{{ \Illuminate\Support\Carbon::parse($d)->format('d/m') }}</small></div>
    @endforeach</div>@endif
  </div>
  <div class="card"><div class="card-h"><h3>Par moyen de paiement</h3></div>
    @forelse($byMethod as $m)<div class="row between mt-2 small"><span class="bold">{{ $pm[$m->payment_method] ?? $m->payment_method }} <span class="muted">({{ $m->c }})</span></span><span>@money($m->t)</span></div>
      <div class="progress mt-1"><span style="width:{{ $revenue ? $m->t / $revenue * 100 : 0 }}%;background:var(--brand)"></span></div>@empty<div class="muted small">—</div>@endforelse
    <div class="card-h mt-3"><h3>Par vendeur</h3></div>
    @forelse($bySeller as $s)<div class="row between mt-1 small"><span>{{ $s->name }} <span class="muted">({{ $s->c }})</span></span><b>@money($s->t)</b></div>@empty<div class="muted small">—</div>@endforelse
  </div>
</div>

<div class="card p0 mt-3"><div class="card-h" style="padding:18px 20px 0"><h3>Top 10 des articles</h3></div><div class="table-wrap"><table class="table">
  <thead><tr><th>#</th><th>Article</th><th class="right">Quantité</th><th class="right">Ventes</th><th class="right">Bénéfice</th></tr></thead>
  <tbody>@forelse($top as $i => $t)<tr><td class="muted">{{ $i + 1 }}</td><td class="bold">{{ $t->name }}</td><td class="right">@qty($t->q)</td><td class="right bold">@money($t->t)</td><td class="right t-green">@money($t->m)</td></tr>
  @empty<tr><td colspan="5"><div class="empty">—</div></td></tr>@endforelse</tbody>
</table></div></div>
@endsection
