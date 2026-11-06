{{-- Reçu affiché à l'écran (vendeur après la vente, ou acheteur via le lien) --}}
@php $t = $sale->tenant; @endphp
<div class="receipt">
  <div class="rh">
    <div class="rlogo">@if($t->logoUrl())<img src="{{ $t->logoUrl() }}" alt="">@else{{ $t->initials() }}@endif</div>
    <div class="bold" style="font-size:18px">{{ $t->name }}</div>
    @if($t->receipt_header)<div class="small muted">{{ $t->receipt_header }}</div>@endif
    <div class="xs muted">{{ $sale->branch->address ?? $t->address }} {{ $t->city ? '· '.$t->city : '' }}</div>
    @if($t->phone)<div class="xs muted">Tél : {{ $t->phone }}</div>@endif
    @if($t->tax_id)<div class="xs muted">NIF/RCCM : {{ $t->tax_id }}</div>@endif
  </div>
  <div class="row between small mt-2"><span class="muted">Reçu</span><b>{{ $sale->number }}</b></div>
  <div class="row between small"><span class="muted">Date</span><span>{{ $sale->sold_at->format('d/m/Y à H:i') }}</span></div>
  @if($sale->user)<div class="row between small"><span class="muted">Vendeur</span><span>{{ $sale->user->name }}</span></div>@endif
  @if($sale->customer_name)<div class="row between small"><span class="muted">Client</span><span>{{ $sale->customer_name }}</span></div>@endif
  @if($sale->status === 'cancelled')<div class="alert alert-err mt-2" style="justify-content:center">VENTE ANNULÉE</div>@endif

  <div style="border-top:1.5px dashed var(--line-2);margin-top:12px;padding-top:6px">
    @foreach($sale->items as $it)
      <div class="ri"><div><div class="bold">{{ $it->name }}</div><div class="xs muted">@qty($it->quantity) × @money($it->unit_price)</div></div><div class="bold">@money($it->total)</div></div>
    @endforeach
  </div>
  @if($sale->discount)<div class="row between small"><span class="muted">Remise</span><span>− @money($sale->discount)</span></div>@endif
  <div class="rt"><span>TOTAL</span><span style="color:var(--brand)">@money($sale->total)</span></div>
  <div class="row between small mt-1"><span class="muted">Payé ({{ $sale->paymentLabel() }})</span><span>@money($sale->paid)</span></div>
  @if($sale->change())<div class="row between small"><span class="muted">Monnaie rendue</span><span>@money($sale->change())</span></div>@endif
  @if($sale->due())<div class="row between small bold t-red"><span>Reste à payer</span><span>@money($sale->due())</span></div>@endif
  <div class="center small muted mt-3">{{ $t->receipt_footer ?: 'Merci pour votre achat !' }}</div>
  <div class="center xs muted mt-1" style="opacity:.7">Reçu généré par {{ config('saas.name') }}</div>
</div>
