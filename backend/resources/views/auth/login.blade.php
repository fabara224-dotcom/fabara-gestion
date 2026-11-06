@extends('layouts.public')
@section('title', 'Connexion · '.config('saas.name'))
@section('content')
<div class="auth">
  @include('auth._side', ['headline' => 'Bon retour !', 'text' => 'Connectez-vous pour retrouver votre caisse, votre stock et vos chiffres du jour.'])
  <div class="form"><div class="box">
    <h2>Connexion</h2>
    <p class="muted mt-1 mb-3">Propriétaire, gérant ou vendeur : utilisez l'e-mail créé pour vous.</p>
    @include('partials.flash')
    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="field"><label class="lbl">E-mail</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="vous@exemple.com"></div>
      <div class="field" x-data="{s:false}">
        <label class="lbl">Mot de passe</label>
        <div style="position:relative"><input class="input" :type="s?'text':'password'" name="password" required>
          <button type="button" @click="s=!s" class="btn btn-ghost btn-sm" style="position:absolute;right:5px;top:5px"><x-icon name="eye" width="16" height="16"/></button></div>
      </div>
      <label class="check mb-3"><input type="checkbox" name="remember" checked> Rester connecté</label>
      <button class="btn btn-primary btn-lg btn-block">Se connecter</button>
    </form>
    <p class="center mt-3 small muted">Pas encore de compte ? <a href="{{ route('register') }}" class="bold t-red">Essai gratuit {{ config('saas.trial_days') }} jours</a></p>
  </div></div>
</div>
@endsection
