@extends('layouts.app')
@section('title', 'Articles')
@section('actions')
  @if(auth()->user()->canManage() || auth()->user()->isSuperAdmin())
    <a href="{{ route('products.create') }}" class="btn btn-primary"><x-icon name="plus"/> Ajouter un article</a>
  @endif
@endsection
@section('content')
@php $manage = auth()->user()->canManage() || auth()->user()->isSuperAdmin(); @endphp
<div class="grid g-main" style="align-items:start">
  <div>
    <form class="row wrap mb-2" method="GET" style="gap:10px">
      <div class="search grow" style="min-width:220px"><x-icon name="search"/><input class="input" name="q" value="{{ request('q') }}" placeholder="Nom, référence ou code-barres"></div>
      <select class="input" name="category" style="width:auto" onchange="this.form.submit()"><option value="">Toutes catégories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') === $c->id)>{{ $c->name }} ({{ $c->products_count }})</option>@endforeach</select>
      <a href="{{ request('stock') === 'low' ? route('products.index') : route('products.index', ['stock' => 'low']) }}" class="chip {{ request('stock') === 'low' ? 'on' : '' }}"><x-icon name="alert" width="15" height="15"/> Stock faible</a>
    </form>

    <div class="card p0"><div class="table-wrap"><table class="table">
      <thead><tr><th>Article</th><th>Catégorie</th><th class="right">Prix de vente</th>@if($manage)<th class="right">Marge</th>@endif<th class="right">Stock</th><th></th></tr></thead>
      <tbody>
      @forelse($products as $p)
        <tr>
          <td><div class="row">
            <div class="thumb">@if($p->image_url)<img src="{{ $p->image_url }}" alt="" loading="lazy">@else<span class="muted bold" style="font-size:13px">{{ mb_strtoupper(mb_substr($p->name, 0, 2)) }}</span>@endif</div>
            <div><div class="bold">{{ $p->name }}</div><div class="xs muted">{{ $p->sku ?: $p->barcode ?: '' }} @if(!$p->is_active)<span class="badge b-gray">masqué en caisse</span>@endif</div></div>
          </div></td>
          <td>@if($p->category)<span class="badge" style="background:color-mix(in srgb,{{ $p->category->color ?? '#999' }} 14%,#fff);color:{{ $p->category->color ?? '#666' }}">{{ $p->category->name }}</span>@else<span class="muted">—</span>@endif</td>
          <td class="right bold">@money($p->price)</td>
          @if($manage)<td class="right small {{ $p->margin() > 0 ? 't-green' : 'muted' }}">{{ $p->cost ? \App\Support\Money::format($p->margin(), '') : '—' }}</td>@endif
          <td class="right">@if($p->track_stock)<span class="badge {{ $p->stock <= 0 ? 'b-red' : ($p->isLow() ? 'b-yellow' : 'b-green') }}">@qty($p->stock) {{ $p->unit }}</span>@else<span class="muted small">illimité</span>@endif</td>
          <td class="right" style="white-space:nowrap">
            @if($manage)
              <a href="{{ route('stock.index', ['product' => $p->id]) }}" class="btn btn-light btn-sm" title="Mouvements de stock"><x-icon name="layers"/></a>
              <a href="{{ route('products.edit', $p) }}" class="btn btn-light btn-sm" title="Modifier"><x-icon name="edit"/></a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty"><div class="ill"><x-icon name="box"/></div><div class="bold" style="color:var(--ink)">Aucun article</div>Ajoutez vos articles avec leur photo, leur prix et la quantité en stock.
          @if($manage)<div class="mt-2"><a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/> Premier article</a></div>@endif</div></td></tr>
      @endforelse
      </tbody>
    </table></div></div>
    {{ $products->links() }}
  </div>

  <div class="grid" style="gap:18px">
    @if($manage)
    <div class="card stat"><div class="ico bg-green"><x-icon name="money"/></div><div class="lbl">Valeur du stock (prix d'achat)</div><div class="val">@money($stockValue)</div></div>
    @endif
    <div class="card">
      <div class="card-h"><h3>Catégories</h3></div>
      @foreach($categories as $c)
        <div class="row between mt-1">
          <a href="{{ route('products.index', ['category' => $c->id]) }}" class="row small"><span style="width:10px;height:10px;border-radius:50%;background:{{ $c->color ?? '#ccc' }}"></span>{{ $c->name }} <span class="muted">({{ $c->products_count }})</span></a>
          @if($manage)<form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Supprimer la catégorie ? Les articles restent.')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm"><x-icon name="trash" width="15" height="15"/></button></form>@endif
        </div>
      @endforeach
      @if($manage)
      <form method="POST" action="{{ route('categories.store') }}" class="row mt-2" style="gap:8px">@csrf
        <input type="color" name="color" value="#E84A8A" style="width:40px;height:38px;border:0;background:none">
        <input class="input sm grow" name="name" placeholder="Nouvelle catégorie" required>
        <button class="btn btn-dark btn-sm"><x-icon name="plus"/></button>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection
