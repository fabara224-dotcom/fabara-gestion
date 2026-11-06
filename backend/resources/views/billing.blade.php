@extends('layouts.app')
@section('title', 'Abonnement')
@section('content')
@php $st = ['trial' => ['Essai gratuit', 'b-blue'], 'active' => ['Actif', 'b-green'], 'past_due' => ['Paiement en retard', 'b-yellow'], 'suspended' => ['Suspendu', 'b-red']][$tenant->status] ?? [$tenant->status, 'b-gray']; @endphp

@unless($tenant->isUsable())
  <div class="alert alert-err"><x-icon name="lock"/><div><b>Votre accès est suspendu.</b> Vos données sont conservées. Réglez votre abonnement pour reprendre immédiatement.</div></div>
@endunless

<div class="grid g-main" style="align-items:start">
  <div class="card">
    <div class="row between"><div><div class="muted small">Formule actuelle</div><div style="font-size:24px;font-weight:800">{{ $tenant->plan->name ?? '—' }}</div></div><span class="badge {{ $st[1] }}">{{ $st[0] }}</span></div>
    <div class="mt-2 small muted">
      @if($tenant->status === 'trial') Fin de l'essai : <b style="color:var(--ink)">{{ $tenant->trial_ends_at?->format('d/m/Y') }}</b> ({{ $tenant->daysLeft() }} jours)
      @elseif($tenant->subscription_ends_at) Payé jusqu'au <b style="color:var(--ink)">{{ $tenant->subscription_ends_at->format('d/m/Y') }}</b> @endif
    </div>
    <div class="grid g-3 mt-3">
      @foreach($plans as $p)
        <div class="price {{ $tenant->plan_id === $p->id ? 'hot' : '' }}" style="padding:20px">
          <div class="bold">{{ $p->name }}</div>
          <div class="amt" style="font-size:24px">{{ number_format($p->price_gnf, 0, ',', ' ') }} <span class="xs muted">GNF/mois</span></div>
          <div class="xs muted">{{ $p->max_users }} utilisateurs · {{ $p->max_branches }} boutique(s)</div>
        </div>
      @endforeach
    </div>
  </div>
  <div class="card">
    <h3 class="bold mb-2" style="font-size:17px">Comment payer ?</h3>
    <ol class="small" style="padding-left:18px;display:grid;gap:8px">
      <li>Envoyez le montant par <b>Orange Money</b> ou <b>MTN MoMo</b> au <b>{{ config('saas.support_phone') }}</b>.</li>
      <li>Indiquez en motif le code : <b class="t-red">{{ $tenant->slug }}</b></li>
      <li>Votre abonnement est activé dès réception.</li>
    </ol>
    <a class="btn btn-green btn-block mt-3" target="_blank" href="https://wa.me/{{ config('saas.support_whatsapp') }}?text={{ rawurlencode('Bonjour, je souhaite payer mon abonnement '.config('saas.name').' pour '.$tenant->name.' (code '.$tenant->slug.').') }}"><x-icon name="whatsapp"/> Contacter le support</a>
    @if($tenant->payments->count())
      <div class="lbl mt-3">Paiements</div>
      @foreach($tenant->payments->sortByDesc('paid_at') as $pay)<div class="row between small mt-1"><span>{{ $pay->paid_at->format('d/m/Y') }} · {{ $pay->months }} mois</span><b>@money($pay->amount)</b></div>@endforeach
    @endif
  </div>
</div>
@endsection
