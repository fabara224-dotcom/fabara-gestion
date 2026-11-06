@php
  $u = auth()->user();
  $t = $currentTenant;
  $brand = $t->brand_color ?? '#D7263D';
  $is = fn ($p) => request()->routeIs($p) ? 'on' : '';
@endphp
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Tableau de bord') · {{ $t->name ?? config('saas.name') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
  <style>:root{--brand:{{ $brand }};--brand-soft:color-mix(in srgb,{{ $brand }} 11%,#fff);--brand-ink:color-mix(in srgb,{{ $brand }} 80%,#000)}</style>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
  @stack('head')
</head>
<body x-data="{ menu:false }">
<div class="shell">
  <div class="scrim" x-show="menu" x-cloak @click="menu=false"></div>
  <aside class="sidebar" :class="menu && 'open'">
    <div class="brandbox">
      <div class="logo-tile">
        @if($t?->logoUrl()) <img src="{{ $t->logoUrl() }}" alt=""> @else {{ $t?->initials() ?? 'FG' }} @endif
      </div>
      <div class="grow">
        <div class="t">{{ $t->name ?? config('saas.name') }}</div>
        <div class="s">{{ $u->branch->name ?? ($u->isSuperAdmin() ? 'Plateforme' : 'Toutes les boutiques') }}</div>
      </div>
    </div>

    <nav class="nav">
      @if($t)
        <a href="{{ route('dashboard') }}" class="{{ $is('dashboard') }}"><x-icon name="home"/> Tableau de bord</a>
        <a href="{{ route('pos') }}" class="{{ $is('pos') }}"><x-icon name="cart"/> Caisse</a>
        <a href="{{ route('sales.index') }}" class="{{ $is('sales.*') }}"><x-icon name="receipt"/> Ventes & reçus</a>
        <a href="{{ route('products.index') }}" class="{{ $is('products.*') }}"><x-icon name="box"/> Articles</a>
        @if($u->canManage() || $u->isSuperAdmin())
          <a href="{{ route('stock.index') }}" class="{{ $is('stock.*') }}"><x-icon name="layers"/> Stock</a>
          <a href="{{ route('customers.index') }}" class="{{ $is('customers.*') }}"><x-icon name="user"/> Clients & crédits</a>
          <a href="{{ route('expenses.index') }}" class="{{ $is('expenses.*') }}"><x-icon name="wallet"/> Dépenses</a>
          <a href="{{ route('reports.index') }}" class="{{ $is('reports.*') }}"><x-icon name="chart"/> Rapports</a>
        @endif
        @if($u->isOwner() || $u->isSuperAdmin())
          <div class="sec">Propriétaire</div>
          <a href="{{ route('team.index') }}" class="{{ $is('team.*') }}"><x-icon name="users"/> Équipe</a>
          <a href="{{ route('settings.edit') }}" class="{{ $is('settings.*') }}"><x-icon name="settings"/> Logo & paramètres</a>
          <a href="{{ route('billing') }}" class="{{ $is('billing') }}"><x-icon name="credit"/> Abonnement</a>
        @endif
      @endif
      @if($u->isSuperAdmin())
        <div class="sec">Plateforme</div>
        <a href="{{ route('admin.tenants.index') }}" class="{{ $is('admin.*') }}"><x-icon name="crown"/> Entreprises clientes</a>
      @endif
    </nav>

    <div class="me">
      <div class="avatar">{{ $u->initials() }}</div>
      <div class="grow"><div class="bold small">{{ $u->name }}</div><div class="xs muted">{{ $u->roleLabel() }}</div></div>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-btn" title="Déconnexion"><x-icon name="logout"/></button></form>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <button class="icon-btn burger" @click="menu=true"><x-icon name="menu"/></button>
      <h1 class="grow">@yield('title', 'Tableau de bord')</h1>
      @yield('actions')
    </header>

    <div class="page">
      @if(session('impersonate_tenant') && $u->isSuperAdmin())
        <div class="alert alert-warn">
          <x-icon name="eye"/> <div class="grow">Mode support : vous êtes dans l'espace de <b>{{ $t?->name }}</b>.</div>
          <form method="POST" action="{{ route('admin.leave') }}">@csrf<button class="btn btn-sm btn-dark">Quitter</button></form>
        </div>
      @endif
      @if($t && $t->status === 'trial' && $t->daysLeft() !== null && $t->daysLeft() <= 5)
        <div class="alert alert-warn"><x-icon name="calendar"/> <div>Essai gratuit : plus que <b>{{ $t->daysLeft() }} jour(s)</b>. <a href="{{ route('billing') }}" class="bold" style="text-decoration:underline">Choisir une formule</a></div></div>
      @elseif($t && $t->status === 'past_due')
        <div class="alert alert-err"><x-icon name="alert"/> <div>Paiement de l'abonnement en retard. Réglez-le pour éviter la suspension. <a href="{{ route('billing') }}" class="bold" style="text-decoration:underline">Voir</a></div></div>
      @endif

      @include('partials.flash')
      @yield('content')
    </div>
  </main>
</div>
@stack('scripts')
</body>
</html>
