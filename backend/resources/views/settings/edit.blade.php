@extends('layouts.app')
@section('title', 'Logo & paramètres')
@section('content')
<div class="grid g-main" style="align-items:start">
  <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="card"
        x-data="{ logo: @js($tenant->logoUrl()), color: '{{ old('brand_color', $tenant->brand_color) }}', name: @js($tenant->name), footer: @js($tenant->receipt_footer) }">
    @csrf @method('PUT')
    <h3 class="bold mb-3" style="font-size:17px">Identité de votre entreprise</h3>
    <div class="row mb-3" style="gap:18px;align-items:flex-start">
      <label style="width:110px;height:110px;border-radius:22px;border:2px dashed var(--line-2);overflow:hidden;cursor:pointer;display:grid;place-items:center;background:var(--bg);flex-shrink:0">
        <template x-if="logo"><img :src="logo" style="width:100%;height:100%;object-fit:cover"></template>
        <span x-show="!logo" class="xs muted center">Ajouter<br>le logo</span>
        <input type="file" name="logo" accept="image/*" hidden @change="const f=$event.target.files[0]; if(f) logo=URL.createObjectURL(f)">
      </label>
      <div class="grow">
        <div class="field"><label class="lbl">Nom affiché</label><input class="input" name="name" x-model="name" required></div>
        <div class="row" style="gap:10px"><label class="lbl" style="margin:0">Couleur</label>
          <input type="color" name="brand_color" x-model="color" style="width:46px;height:38px;border:0;background:none">
          @foreach(['#D7263D','#E84A8A','#1B998B','#4a5bd4','#F4B400','#151a26'] as $c)<button type="button" @click="color='{{ $c }}'" style="width:26px;height:26px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px var(--line-2);background:{{ $c }};cursor:pointer"></button>@endforeach
        </div>
        @if($tenant->logo_path)<label class="check mt-2 small"><input type="checkbox" name="remove_logo" value="1"> Retirer le logo</label>@endif
      </div>
    </div>
    <div class="grid g-2" style="gap:14px">
      <div class="field"><label class="lbl">Type de commerce</label><select class="input" name="business_type">@foreach($types as $k => $v)<option value="{{ $k }}" @selected($tenant->business_type === $k)>{{ $v }}</option>@endforeach</select></div>
      <div class="field"><label class="lbl">Téléphone</label><input class="input" name="phone" value="{{ old('phone', $tenant->phone) }}"></div>
      <div class="field"><label class="lbl">E-mail</label><input class="input" type="email" name="email" value="{{ old('email', $tenant->email) }}"></div>
      <div class="field"><label class="lbl">NIF / RCCM</label><input class="input" name="tax_id" value="{{ old('tax_id', $tenant->tax_id) }}"></div>
      <div class="field"><label class="lbl">Adresse</label><input class="input" name="address" value="{{ old('address', $tenant->address) }}"></div>
      <div class="field"><label class="lbl">Ville</label><input class="input" name="city" value="{{ old('city', $tenant->city) }}"></div>
    </div>
    <h3 class="bold mb-2 mt-2" style="font-size:17px">Reçus</h3>
    <div class="field"><label class="lbl">Ligne sous le nom (slogan, horaires…)</label><input class="input" name="receipt_header" value="{{ old('receipt_header', $tenant->receipt_header) }}" placeholder="Ex : Ouvert 7j/7 de 8h à 21h"></div>
    <div class="field"><label class="lbl">Message en bas du reçu</label><input class="input" name="receipt_footer" x-model="footer" placeholder="Merci pour votre achat !"></div>
    <button class="btn btn-primary btn-lg">Enregistrer</button>

    <div class="mt-4">
      <div class="lbl">Aperçu</div>
      <div class="row" style="gap:12px;padding:14px;border-radius:14px;background:var(--bg)">
        <div style="width:44px;height:44px;border-radius:12px;overflow:hidden;display:grid;place-items:center;color:#fff;font-weight:800" :style="'background:'+color"><template x-if="logo"><img :src="logo" style="width:100%;height:100%;object-fit:cover"></template><span x-show="!logo" x-text="name.substring(0,2).toUpperCase()"></span></div>
        <div class="grow"><b x-text="name"></b><div class="xs muted" x-text="footer || 'Merci pour votre achat !'"></div></div>
        <span class="btn btn-sm" style="color:#fff" :style="'background:'+color">Encaisser</span>
      </div>
    </div>
  </form>

  <div class="card">
    <h3 class="bold mb-2" style="font-size:17px">Vos boutiques</h3>
    @foreach($branches as $b)
      <form method="POST" action="{{ route('branches.update', $b) }}" class="mb-2" style="padding:12px;border:1px solid var(--line);border-radius:12px">@csrf @method('PUT')
        <input class="input sm mb-2" name="name" value="{{ $b->name }}" required>
        <div class="row" style="gap:6px"><input class="input sm" name="address" value="{{ $b->address }}" placeholder="Adresse"><input class="input sm" name="phone" value="{{ $b->phone }}" placeholder="Téléphone" style="width:130px"><button class="btn btn-light btn-sm"><x-icon name="check"/></button></div>
      </form>
    @endforeach
    <form method="POST" action="{{ route('branches.store') }}" class="mt-3">@csrf
      <div class="lbl">Ajouter une boutique</div>
      <div class="row" style="gap:6px"><input class="input sm grow" name="name" placeholder="Ex : Boutique Kaloum" required><button class="btn btn-dark btn-sm"><x-icon name="plus"/></button></div>
      <div class="hint">Chaque boutique a ses vendeurs et ses ventes ; vous voyez tout.</div>
    </form>
  </div>
</div>
@endsection
