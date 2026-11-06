@extends('layouts.public')
@section('title', 'Créer mon entreprise · '.config('saas.name'))
@section('content')
<div class="auth">
  @include('auth._side', ['headline' => 'Votre commerce, enfin bien organisé.', 'text' => config('saas.trial_days').' jours gratuits, sans engagement. Vous ajoutez vos vendeurs ensuite.'])
  <div class="form"><div class="box" style="max-width:480px">
    <h2>Créer mon entreprise</h2>
    <p class="muted mt-1 mb-3">2 minutes, et vous pouvez vendre.</p>
    @include('partials.flash')
    <form method="POST" action="{{ route('register') }}">
      @csrf
      <input type="hidden" name="plan" value="{{ $selectedPlan }}">
      <div class="lbl" style="color:var(--brand);text-transform:uppercase;font-size:11px;letter-spacing:.08em">Votre commerce</div>
      <div class="field"><label class="lbl">Nom de l'entreprise</label><input class="input" name="business_name" value="{{ old('business_name') }}" required placeholder="Ex : Boutique Mariama"></div>
      <div class="grid g-2" style="gap:12px">
        <div class="field"><label class="lbl">Type de commerce</label>
          <select class="input" name="business_type">@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('business_type') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label class="lbl">Ville</label><input class="input" name="city" value="{{ old('city', 'Conakry') }}"></div>
      </div>
      <div class="field"><label class="lbl">Téléphone</label><input class="input" name="phone" value="{{ old('phone') }}" required placeholder="6XX XX XX XX"></div>

      <div class="lbl mt-2" style="color:var(--brand);text-transform:uppercase;font-size:11px;letter-spacing:.08em">Vous (propriétaire)</div>
      <div class="field"><label class="lbl">Votre nom</label><input class="input" name="name" value="{{ old('name') }}" required></div>
      <div class="field"><label class="lbl">E-mail</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
      <div class="grid g-2" style="gap:12px">
        <div class="field"><label class="lbl">Mot de passe</label><input class="input" type="password" name="password" required minlength="8"></div>
        <div class="field"><label class="lbl">Confirmer</label><input class="input" type="password" name="password_confirmation" required></div>
      </div>
      <button class="btn btn-primary btn-lg btn-block mt-1">Démarrer mon essai gratuit</button>
      <p class="xs muted center mt-2">En créant un compte, vous acceptez que vos données soient stockées de façon sécurisée et visibles uniquement par votre entreprise.</p>
    </form>
    <p class="center mt-3 small muted">Déjà inscrit ? <a href="{{ route('login') }}" class="bold t-red">Se connecter</a></p>
  </div></div>
</div>
@endsection
