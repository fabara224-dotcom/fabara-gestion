@extends('layouts.public')
@section('title', 'Reçu '.$sale->number.' · '.$sale->tenant->name)
@push('head')
  @php $b = $sale->tenant->brand_color ?: '#D7263D'; @endphp
  <style>:root{--brand:{{ $b }};--brand-soft:color-mix(in srgb,{{ $b }} 11%,#fff)} body{background:linear-gradient(180deg,var(--brand-soft),#f7f8fb 40%)}</style>
  <meta name="robots" content="noindex">
@endpush
@section('content')
<div style="padding:36px 16px 60px">
  @include('receipts._card')
  <div class="row center no-print" style="justify-content:center;margin-top:34px;gap:10px;flex-wrap:wrap">
    <a href="{{ route('receipt.public.pdf', $sale->receipt_token) }}" class="btn btn-primary"><x-icon name="download"/> Télécharger le PDF</a>
    <button onclick="window.print()" class="btn btn-light"><x-icon name="printer"/> Imprimer</button>
  </div>
</div>
@endsection
