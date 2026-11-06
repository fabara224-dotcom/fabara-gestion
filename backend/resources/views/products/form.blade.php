@extends('layouts.app')
@section('title', $product->exists ? 'Modifier « '.$product->name.' »' : 'Nouvel article')
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
      x-data="{ preview: @js($product->image_url), price: {{ (int) old('price', $product->price) }}, cost: {{ (int) old('cost', $product->cost) }} }">
  @csrf @if($product->exists) @method('PUT') @endif

  <div class="grid g-main" style="align-items:start">
    <div class="card">
      <div class="field"><label class="lbl">Nom de l'article *</label><input class="input" name="name" value="{{ old('name', $product->name) }}" required autofocus placeholder="Ex : Robe wax taille M"></div>
      <div class="grid g-2" style="gap:14px">
        <div class="field"><label class="lbl">Prix de vente ({{ $currentTenant->currency }}) *</label><input class="input" type="number" min="0" name="price" x-model.number="price" required></div>
        <div class="field"><label class="lbl">Prix d'achat ({{ $currentTenant->currency }})</label><input class="input" type="number" min="0" name="cost" x-model.number="cost"><div class="hint">Sert à calculer votre bénéfice. Jamais affiché au client.</div></div>
      </div>
      <div class="alert alert-ok" x-show="price && cost" x-cloak style="margin-top:-4px">Marge par article : <b x-text="(price-cost).toLocaleString('fr-FR')+' {{ $currentTenant->currency }}'"></b> <span x-text="cost ? '('+Math.round((price-cost)/cost*100)+' %)' : ''"></span></div>
      <div class="grid g-2" style="gap:14px">
        <div class="field"><label class="lbl">Catégorie</label>
          <select class="input" name="category_id"><option value="">— Aucune —</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) === $c->id)>{{ $c->name }}</option>@endforeach</select>
          <div class="hint">Créez vos catégories depuis la liste des articles.</div></div>
        <div class="field"><label class="lbl">Unité</label>
          <select class="input" name="unit">@foreach(['pièce','paire','kg','g','litre','mètre','carton','paquet','boîte','sac','lot'] as $un)<option @selected(old('unit', $product->unit) === $un)>{{ $un }}</option>@endforeach</select></div>
      </div>
      <div class="grid g-2" style="gap:14px">
        <div class="field"><label class="lbl">Référence</label><input class="input" name="sku" value="{{ old('sku', $product->sku) }}"></div>
        <div class="field"><label class="lbl">Code-barres</label><input class="input" name="barcode" value="{{ old('barcode', $product->barcode) }}" placeholder="Scannez avec une douchette"></div>
      </div>
      <div class="field"><label class="lbl">Description</label><textarea class="input" name="description" rows="3">{{ old('description', $product->description) }}</textarea></div>
    </div>

    <div class="grid" style="gap:18px">
      <div class="card">
        <label class="lbl">Photo principale</label>
        <label style="display:block;aspect-ratio:1/1;border:2px dashed var(--line-2);border-radius:16px;overflow:hidden;cursor:pointer;background:var(--bg);position:relative">
          <template x-if="preview"><img :src="preview" style="width:100%;height:100%;object-fit:cover"></template>
          <div x-show="!preview" style="position:absolute;inset:0;display:grid;place-items:center;text-align:center" class="muted small"><div><x-icon name="image" width="34" height="34" style="margin:0 auto 8px"/>Touchez pour ajouter une photo<br><span class="xs">JPG ou PNG, 5 Mo max.</span></div></div>
          <input type="file" name="image" accept="image/*" capture="environment" hidden @change="const f=$event.target.files[0]; if(f) preview=URL.createObjectURL(f)">
        </label>
        @if($product->image_path)<label class="check mt-2 small"><input type="checkbox" name="remove_image" value="1"> Retirer la photo</label>@endif
        <label class="lbl mt-3">Autres photos (jusqu'à 6)</label>
        <input class="input" type="file" name="gallery[]" accept="image/*" multiple style="padding-top:10px">
        @if($product->gallery)<div class="row wrap mt-2" style="gap:6px">@foreach($product->gallery as $g)<img src="{{ Storage::disk('public')->url($g) }}" class="thumb" style="width:52px;height:52px">@endforeach</div>@endif
      </div>

      <div class="card">
        <label class="check mb-2"><input type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock ?? true))> Suivre le stock de cet article</label>
        @unless($product->exists)
          <div class="field"><label class="lbl">Quantité en stock actuellement</label><input class="input" type="number" step="any" name="initial_stock" value="{{ old('initial_stock', 0) }}"></div>
        @else
          <div class="small muted mb-2">Stock actuel : <b style="color:var(--ink)">@qty($product->stock) {{ $product->unit }}</b> — <a class="t-red bold" href="{{ route('stock.index', ['product' => $product->id]) }}">ajuster le stock</a></div>
        @endunless
        <div class="field"><label class="lbl">M'alerter quand il reste</label><input class="input" type="number" step="any" min="0" name="alert_threshold" value="{{ old('alert_threshold', $product->alert_threshold) }}"></div>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Visible à la caisse</label>
      </div>

      <div class="grid" style="gap:10px">
        <button class="btn btn-primary btn-lg btn-block"><x-icon name="check"/> Enregistrer</button>
        @unless($product->exists)<button name="add_another" value="1" class="btn btn-light btn-block">Enregistrer et ajouter un autre</button>@endunless
        <a href="{{ route('products.index') }}" class="btn btn-ghost btn-block">Annuler</a>
      </div>
    </div>
  </div>
</form>

@if($product->exists)
  <form method="POST" action="{{ route('products.destroy', $product) }}" class="mt-3" onsubmit="return confirm('Supprimer cet article ? L\'historique des ventes est conservé.')">
    @csrf @method('DELETE') <button class="btn btn-danger btn-sm"><x-icon name="trash"/> Supprimer l'article</button>
  </form>
@endif
@endsection
