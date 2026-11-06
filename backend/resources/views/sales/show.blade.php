@extends('layouts.app')
@section('title', 'Reçu '.$sale->number)
@section('actions')<a href="{{ route('pos') }}" class="btn btn-primary"><x-icon name="plus"/> Nouvelle vente</a>@endsection
@section('content')
<div class="grid g-main" style="align-items:start">
  <div style="padding:10px 0 30px">@include('receipts._card')</div>

  <div class="card no-print">
    <h3 class="bold mb-2" style="font-size:17px">Remettre le reçu à l'acheteur</h3>
    <p class="small muted mb-3">Choisissez comment donner le reçu à votre client.</p>
    <div class="grid" style="gap:10px">
      <a href="{{ route('sales.receipt', [$sale, 'format' => 'ticket']) }}" target="_blank" class="btn btn-dark btn-lg btn-block"><x-icon name="printer"/> Imprimer le ticket (80 mm)</a>
      <a href="{{ route('sales.receipt', [$sale, 'format' => 'a4', 'download' => 1]) }}" class="btn btn-light btn-lg btn-block"><x-icon name="download"/> Télécharger en PDF (A4)</a>

      @if($sale->whatsappUrl())
        <a href="{{ $sale->whatsappUrl() }}" target="_blank" class="btn btn-green btn-lg btn-block"><x-icon name="whatsapp"/> Envoyer par WhatsApp au {{ $sale->customer_phone }}</a>
      @else
        <form x-data="{p:''}" @submit.prevent="window.open('https://wa.me/'+(p.replace(/\D/g,'').length===9?'224':'')+p.replace(/\D/g,'')+'?text='+encodeURIComponent('Bonjour, voici votre reçu {{ $sale->number }} ({{ addslashes($sale->tenant->name) }}) : {{ $sale->publicUrl() }}'))" class="row">
          <input class="input grow" x-model="p" placeholder="N° WhatsApp de l'acheteur" required>
          <button class="btn btn-green"><x-icon name="whatsapp"/></button>
        </form>
      @endif

      <div x-data="{c:false}" class="row">
        <input class="input grow small" readonly value="{{ $sale->publicUrl() }}">
        <button class="btn btn-light" @click="navigator.clipboard.writeText('{{ $sale->publicUrl() }}');c=true;setTimeout(()=>c=false,2000)"><span x-text="c?'Copié ✓':'Copier le lien'"></span></button>
      </div>
    </div>

    @if((auth()->user()->canManage() || auth()->user()->isSuperAdmin()) && $sale->status !== 'cancelled')
      <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="mt-4" onsubmit="return confirm('Annuler cette vente ? Les articles seront remis en stock.')">
        @csrf <button class="btn btn-danger btn-sm"><x-icon name="x"/> Annuler la vente</button>
      </form>
    @endif
  </div>
</div>
@endsection
