@extends('layouts.app')
@section('title', 'Stock')
@section('content')
<div class="grid g-main" style="align-items:start">
  <div>
    <form class="row wrap mb-2" method="GET" style="gap:10px">
      <select class="input" name="product" style="width:auto;max-width:300px" onchange="this.form.submit()"><option value="">Tous les articles</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected($selected === $p->id)>{{ $p->name }}</option>@endforeach</select>
      <select class="input" name="type" style="width:auto" onchange="this.form.submit()"><option value="">Tous les mouvements</option>@foreach($types as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select>
    </form>
    <div class="card p0"><div class="table-wrap"><table class="table">
      <thead><tr><th>Date</th><th>Article</th><th>Type</th><th>Motif</th><th>Par</th><th class="right">Quantité</th></tr></thead>
      <tbody>
      @forelse($movements as $m)
        <tr>
          <td class="small">{{ $m->created_at->format('d/m/Y H:i') }}</td>
          <td class="bold">{{ $m->product->name ?? '—' }}</td>
          <td><span class="badge {{ ['in' => 'b-green', 'out' => 'b-red', 'sale' => 'b-blue', 'adjust' => 'b-yellow', 'return' => 'b-gray'][$m->type] ?? 'b-gray' }}">{{ explode(' (', $types[$m->type] ?? $m->type)[0] }}</span></td>
          <td class="small muted">{{ $m->reason }}</td>
          <td class="small">{{ $m->user->name ?? '—' }}</td>
          <td class="right bold {{ $m->quantity > 0 ? 't-green' : 't-red' }}">{{ $m->quantity > 0 ? '+' : '' }}@qty($m->quantity)</td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty"><div class="ill"><x-icon name="layers"/></div>Aucun mouvement de stock.</div></td></tr>
      @endforelse
      </tbody>
    </table></div></div>
    {{ $movements->links() }}
  </div>

  <div class="card" style="position:sticky;top:90px" x-data="{type:'in'}">
    <h3 class="bold mb-2" style="font-size:17px">Mettre à jour le stock</h3>
    <form method="POST" action="{{ route('stock.store') }}">@csrf
      <div class="field"><label class="lbl">Article</label>
        <select class="input" name="product_id" required><option value="">Choisir…</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected($selected === $p->id)>{{ $p->name }} (stock : {{ \App\Support\Money::qty($p->stock) }})</option>@endforeach</select></div>
      <div class="pay mb-3" style="grid-template-columns:repeat(3,1fr)">
        <label><input type="radio" name="type" value="in" x-model="type"><span>➕ Entrée</span></label>
        <label><input type="radio" name="type" value="out" x-model="type"><span>➖ Sortie</span></label>
        <label><input type="radio" name="type" value="adjust" x-model="type"><span>🧮 Inventaire</span></label>
      </div>
      <div class="field"><label class="lbl" x-text="type==='adjust' ? 'Quantité réellement comptée' : 'Quantité'"></label><input class="input" type="number" step="any" min="0" name="quantity" required></div>
      <div class="field"><label class="lbl">Motif</label><input class="input" name="reason" :placeholder="type==='in' ? 'Ex : Arrivage fournisseur' : (type==='out' ? 'Ex : Article abîmé' : 'Ex : Inventaire fin de mois')"></div>
      <button class="btn btn-primary btn-block btn-lg">Enregistrer</button>
      <p class="hint mt-2">Chaque mouvement est tracé avec le nom de la personne : le propriétaire voit tout.</p>
    </form>
  </div>
</div>
@endsection
