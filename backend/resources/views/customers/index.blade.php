@extends('layouts.app')
@section('title', 'Clients & crédits')
@section('content')
<div class="grid g-3 mb-3">
  <div class="card stat"><div class="ico bg-yellow"><x-icon name="credit"/></div><div class="lbl">Total des crédits clients</div><div class="val t-orange">@money($totalDebt)</div><div class="sub">argent que vos clients vous doivent</div></div>
</div>
<form class="row wrap mb-2" method="GET" style="gap:10px">
  <div class="search grow" style="min-width:220px"><x-icon name="search"/><input class="input" name="q" value="{{ request('q') }}" placeholder="Nom ou téléphone"></div>
  <a href="{{ request('filtre') === 'credit' ? route('customers.index') : route('customers.index', ['filtre' => 'credit']) }}" class="chip {{ request('filtre') === 'credit' ? 'on' : '' }}">Avec crédit seulement</a>
</form>
<div class="card p0"><div class="table-wrap"><table class="table">
  <thead><tr><th>Client</th><th>Téléphone</th><th class="right">Achats</th><th class="right">Doit</th><th></th></tr></thead>
  <tbody>
  @forelse($customers as $c)
    <tr x-data="{o:false}">
      <td><div class="row"><div class="avatar" style="width:34px;height:34px;font-size:12px;background:var(--ink)">{{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}</div><b>{{ $c->name }}</b></div></td>
      <td class="small">{{ $c->phone }}</td>
      <td class="right small">{{ $c->sales_count }}</td>
      <td class="right bold {{ $c->balance > 0 ? 't-red' : 'muted' }}">@money($c->balance)</td>
      <td class="right">
        @if($c->balance > 0)
          <button class="btn btn-light btn-sm" @click="o=!o">Encaisser un remboursement</button>
          <form x-show="o" x-cloak method="POST" action="{{ route('customers.pay', $c) }}" class="row mt-1" style="justify-content:flex-end;gap:6px">@csrf
            <input class="input sm" type="number" name="amount" min="1" max="{{ $c->balance }}" value="{{ $c->balance }}" style="width:140px"><button class="btn btn-primary btn-sm">OK</button></form>
        @endif
        @php $ph = preg_replace('/\D/', '', (string) $c->phone); @endphp
        @if($c->balance > 0 && $ph)
          <a class="btn btn-green btn-sm" target="_blank" href="https://wa.me/{{ strlen($ph) === 9 ? '224'.$ph : $ph }}?text={{ rawurlencode('Bonjour '.$c->name.', petit rappel : il reste '.\App\Support\Money::format($c->balance).' à régler chez '.$currentTenant->name.'. Merci !') }}"><x-icon name="whatsapp"/></a>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="5"><div class="empty"><div class="ill"><x-icon name="user"/></div>Les clients sont créés automatiquement quand vous saisissez leur téléphone à la caisse.</div></td></tr>
  @endforelse
  </tbody>
</table></div></div>
{{ $customers->links() }}
@endsection
