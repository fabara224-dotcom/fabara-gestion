@extends('layouts.app')
@section('title', 'Ventes & reçus')
@section('actions')<a href="{{ route('pos') }}" class="btn btn-primary"><x-icon name="plus"/> Nouvelle vente</a>@endsection
@section('content')
<form class="card mb-3" method="GET">
  <div class="row wrap" style="gap:10px">
    <div class="search grow" style="min-width:220px"><x-icon name="search"/><input class="input sm" name="q" value="{{ request('q') }}" placeholder="N° de reçu, client, téléphone"></div>
    <input class="input sm" type="date" name="from" value="{{ request('from') }}" style="width:auto">
    <input class="input sm" type="date" name="to" value="{{ request('to') }}" style="width:auto">
    @if(auth()->user()->role !== 'cashier')
      <select class="input sm" name="seller" style="width:auto"><option value="">Tous les vendeurs</option>@foreach($sellers as $s)<option value="{{ $s->id }}" @selected(request('seller') == $s->id)>{{ $s->name }}</option>@endforeach</select>
    @endif
    <select class="input sm" name="method" style="width:auto"><option value="">Tous paiements</option>@foreach($methods as $k => $v)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $v }}</option>@endforeach</select>
    <button class="btn btn-dark btn-sm">Filtrer</button>
  </div>
</form>

<div class="grid g-3 mb-3">
  <div class="card stat"><div class="lbl">Ventes trouvées</div><div class="val">{{ $totals->c }}</div></div>
  <div class="card stat"><div class="lbl">Montant total</div><div class="val t-red">@money($totals->t)</div></div>
  <div class="card stat"><div class="lbl">Panier moyen</div><div class="val">@money($totals->c ? $totals->t / $totals->c : 0)</div></div>
</div>

<div class="card p0"><div class="table-wrap"><table class="table">
  <thead><tr><th>Reçu</th><th>Date</th><th>Vendeur</th><th>Client</th><th>Paiement</th><th class="right">Total</th><th></th></tr></thead>
  <tbody>
  @forelse($sales as $s)
    <tr>
      <td><a href="{{ route('sales.show', $s) }}" class="bold">{{ $s->number }}</a> @if($s->status === 'cancelled')<span class="badge b-red">Annulée</span>@endif
        @if($s->device_id && $s->device_id !== 'web')<div class="xs muted"><x-icon name="phone" width="12" height="12"/> mobile</div>@endif</td>
      <td class="small">{{ $s->sold_at->format('d/m/Y H:i') }}</td>
      <td class="small">{{ $s->user->name ?? '—' }}<div class="xs muted">{{ $s->branch->name ?? '' }}</div></td>
      <td class="small">{{ $s->customer_name ?: '—' }}<div class="xs muted">{{ $s->customer_phone }}</div></td>
      <td><span class="badge {{ $s->payment_method === 'credit' ? 'b-yellow' : 'b-gray' }}">{{ $s->paymentLabel() }}</span></td>
      <td class="right bold" style="{{ $s->status === 'cancelled' ? 'text-decoration:line-through;color:#9aa1ae' : '' }}">@money($s->total)</td>
      <td class="right"><a href="{{ route('sales.receipt', $s) }}" target="_blank" class="btn btn-light btn-sm"><x-icon name="printer"/></a></td>
    </tr>
  @empty
    <tr><td colspan="7"><div class="empty"><div class="ill"><x-icon name="receipt"/></div>Aucune vente sur cette période.</div></td></tr>
  @endforelse
  </tbody>
</table></div></div>
{{ $sales->links() }}
@endsection
