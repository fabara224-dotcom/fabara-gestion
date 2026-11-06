@extends('layouts.public')
@section('body_class', 'lp')
@section('content')
@php $stripes = '<div class="stripes"><i style="background:#D7263D"></i><i style="background:#F4B400"></i><i style="background:#1B998B"></i><i style="background:#E84A8A"></i></div>'; @endphp

<div class="wrap">
  <nav class="lp-nav">
    <a href="/" class="lp-logo">{!! $stripes !!} {{ config('saas.name') }}</a>
    <div class="links"><a href="#fonctions">Fonctions</a><a href="#proprietaire">Propriétaires</a><a href="#tarifs">Tarifs</a><a href="#contact">Contact</a></div>
    <a href="{{ route('login') }}" class="btn btn-ghost">Connexion</a>
    <a href="{{ route('register') }}" class="btn btn-primary">Essai gratuit</a>
  </nav>

  <section class="hero">
    <div>
      <span class="pill-tag"><x-icon name="wifi-off" width="16" height="16"/> Marche même sans internet</span>
      <h1>Gérez votre boutique <em>simplement</em>, où que vous soyez.</h1>
      <p class="lead">Caisse en un clic, stock avec photos, reçu pour chaque acheteur, et un œil sur votre commerce depuis votre téléphone — même quand le réseau coupe.</p>
      <div class="row wrap">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Commencer gratuitement <x-icon name="arrow-right"/></a>
        <a href="https://wa.me/{{ config('saas.support_whatsapp') }}" class="btn btn-light btn-lg"><x-icon name="whatsapp"/> Une démo sur WhatsApp</a>
      </div>
      <p class="small muted mt-2">{{ config('saas.trial_days') }} jours gratuits · sans engagement · paiement Orange Money / MoMo</p>
    </div>

    <div style="position:relative">
      <div class="mock">
        <div class="win">
          <div class="wbar"><i></i><i></i><i></i></div>
          <div style="padding:18px">
            <div class="row between mb-2"><b>Aujourd'hui</b><span class="badge b-green"><i></i>En ligne</span></div>
            <div class="grid g-3" style="gap:10px">
              <div class="card" style="padding:12px"><div class="xs muted">Ventes</div><div class="bold t-red" style="font-size:18px">3,4 M</div></div>
              <div class="card" style="padding:12px"><div class="xs muted">Bénéfice</div><div class="bold t-green" style="font-size:18px">1,1 M</div></div>
              <div class="card" style="padding:12px"><div class="xs muted">Reçus</div><div class="bold" style="font-size:18px">42</div></div>
            </div>
            <div class="bars" style="height:150px">
              @foreach([40,62,35,80,58,95,72] as $i => $h)
                <div class="b"><span style="height:{{ $h }}%"></span><small>{{ ['L','M','M','J','V','S','D'][$i] }}</small></div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div class="phone"><div class="scr">
        <div class="bold" style="font-size:12px">Reçu REC-000148</div>
        <div class="muted" style="font-size:10px">Boutique Mariama · 14:32</div>
        <div style="border-top:1px dashed #ddd;margin:8px 0;padding-top:6px">
          <div class="row between"><span>Robe wax ×2</span><b>500 000</b></div>
          <div class="row between"><span>Sac à main</span><b>180 000</b></div>
        </div>
        <div class="row between bold" style="font-size:13px;border-top:1px dashed #ddd;padding-top:6px"><span>Total</span><span class="t-red">680 000</span></div>
        <div class="btn btn-green btn-sm btn-block mt-2" style="font-size:11px">Envoyer par WhatsApp</div>
      </div></div>
    </div>
  </section>
</div>

<section class="section" id="fonctions" style="background:var(--bg)">
  <div class="wrap">
    <h2>Tout ce qu'il faut pour un commerce</h2>
    <p class="sub">Boutiques de quartier, shops, prêt-à-porter, quincailleries, pharmacies, cosmétiques, téléphonie… un seul logiciel, adapté à chacun.</p>
    <div class="grid g-3">
      @foreach([
        ['cart','bg-red','Caisse ultra simple','Touchez l\'article, le total se calcule. Espèces, Orange Money, MoMo ou crédit client.'],
        ['receipt','bg-green','Reçu pour l\'acheteur','Ticket imprimé, PDF, ou lien envoyé par WhatsApp avec votre logo.'],
        ['image','bg-pink','Stock avec photos','Ajoutez vos articles en photo depuis le téléphone. Alerte quand il en reste peu.'],
        ['wifi-off','bg-yellow','Fonctionne hors ligne','Les ventes continuent sans réseau et se synchronisent toutes seules au retour.'],
        ['chart','bg-blue','Bénéfices réels','Chiffre d\'affaires, coût d\'achat, dépenses : vous savez ce que vous gagnez vraiment.'],
        ['user','bg-red','Clients à crédit','Suivez qui vous doit combien, et enregistrez les remboursements.'],
      ] as [$i,$c,$h,$p])
        <div class="feat"><div class="fi {{ $c }}"><x-icon :name="$i"/></div><h4>{{ $h }}</h4><p>{{ $p }}</p></div>
      @endforeach
    </div>
  </div>
</section>

<section class="section" id="proprietaire">
  <div class="wrap grid g-2" style="align-items:center;gap:50px">
    <div>
      <span class="pill-tag"><x-icon name="eye" width="16" height="16"/> Pour les propriétaires</span>
      <h2 style="text-align:left">Votre boutique est tenue par une autre personne ? Gardez un œil dessus.</h2>
      <p class="muted mt-2" style="font-size:17px">Votre gérante vend, vous suivez en direct depuis votre téléphone : chaque vente, chaque reçu, le stock restant et ce que chaque vendeur a encaissé aujourd'hui.</p>
      <div class="grid mt-3" style="gap:12px">
        @foreach(['Ventes de chaque vendeur, en temps réel','Annulations et mouvements de stock tracés','Désactivez un compte en un clic (téléphone perdu, départ)','Vos données ne sont visibles que par votre entreprise'] as $l)
          <div class="row"><span class="badge b-green" style="padding:6px"><x-icon name="check" width="14" height="14"/></span> {{ $l }}</div>
        @endforeach
      </div>
    </div>
    <div class="card" style="padding:0;overflow:hidden">
      <div style="padding:16px 18px;border-bottom:1px solid var(--line)" class="row between"><b>Équipe — aujourd'hui</b><span class="badge b-blue">Vue propriétaire</span></div>
      <table class="table">
        <tr><td><div class="row"><div class="avatar" style="width:32px;height:32px;font-size:12px">AC</div>Awa C. <span class="xs muted">Gérante</span></div></td><td class="right bold">2 450 000 GNF</td></tr>
        <tr><td><div class="row"><div class="avatar" style="width:32px;height:32px;font-size:12px">IB</div>Ibrahima B. <span class="xs muted">Vendeur</span></div></td><td class="right bold">980 000 GNF</td></tr>
        <tr><td><div class="row"><div class="avatar" style="width:32px;height:32px;font-size:12px">FS</div>Fatou S. <span class="xs muted">Vendeuse</span></div></td><td class="right bold">615 000 GNF</td></tr>
      </table>
    </div>
  </div>
</section>

<section class="section" id="tarifs" style="background:var(--bg)">
  <div class="wrap">
    <h2>Des tarifs simples, par mois</h2>
    <p class="sub">Commencez par {{ config('saas.trial_days') }} jours d'essai gratuit. Payez ensuite par Orange Money, MTN MoMo ou en espèces.</p>
    <div class="grid g-3">
      @foreach($plans as $plan)
        <div class="price {{ $plan->code === 'pro' ? 'hot' : '' }}">
          @if($plan->code === 'pro')<span class="badge b-red" style="position:absolute;top:20px;right:20px">Le plus choisi</span>@endif
          <div class="bold" style="font-size:18px">{{ $plan->name }}</div>
          <div class="amt">{{ number_format($plan->price_gnf, 0, ',', ' ') }} <span style="font-size:16px;font-weight:600" class="muted">GNF/mois</span></div>
          <div class="small muted">{{ $plan->max_users }} utilisateurs · {{ $plan->max_branches }} boutique(s) · {{ number_format($plan->max_products, 0, ',', ' ') }} articles</div>
          <ul>@foreach($plan->features ?? [] as $f)<li>{{ $f }}</li>@endforeach</ul>
          <a href="{{ route('register', ['formule' => $plan->code]) }}" class="btn {{ $plan->code === 'pro' ? 'btn-primary' : 'btn-light' }} btn-block">Essayer gratuitement</a>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="section" id="contact">
  <div class="wrap">
    <div class="cta-band">
      <div>
        <h2 style="text-align:left;color:#fff">Prêt à moderniser votre commerce ?</h2>
        <p style="opacity:.75;font-size:17px;margin-top:8px">Installation en 5 minutes. On vous accompagne pour ajouter vos premiers articles.</p>
      </div>
      <div class="row wrap">
        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Créer mon compte</a>
        <a href="tel:{{ preg_replace('/\s/', '', config('saas.support_phone')) }}" class="btn btn-light btn-lg"><x-icon name="phone"/> {{ config('saas.support_phone') }}</a>
      </div>
    </div>
  </div>
</section>

<footer class="foot"><div class="wrap row between wrap">
  <div class="lp-logo" style="font-size:15px">{!! $stripes !!} {{ config('saas.name') }}</div>
  <div>© {{ date('Y') }} {{ config('saas.name') }} · Conakry, Guinée</div>
</div></footer>
@endsection
