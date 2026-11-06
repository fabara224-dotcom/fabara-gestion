@php
  $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ').' '.$tenant->currency;
  $q = fn ($n) => \App\Support\Money::qty($n);
  $ticket = $format === 'ticket';
  $brand = $tenant->brand_color ?: '#D7263D';
@endphp
<!doctype html>
<html lang="fr"><head><meta charset="utf-8">
<style>
  @page { margin: {{ $ticket ? '8px 10px' : '36px 40px' }}; }
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: {{ $ticket ? '9px' : '11px' }}; color: #111; }
  .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; } .m { color: #555; }
  .dash { border-top: 1px dashed #999; margin: 6px 0; }
  table { width: 100%; border-collapse: collapse; }
  td, th { padding: {{ $ticket ? '2px 0' : '7px 6px' }}; vertical-align: top; }
  th { text-align: left; font-size: 9px; text-transform: uppercase; color: #666; border-bottom: 1px solid #ddd; }
  .tot td { font-size: {{ $ticket ? '13px' : '16px' }}; font-weight: bold; padding-top: 6px; }
  .brand { color: {{ $brand }}; }
  .head { {{ $ticket ? '' : 'border-bottom: 3px solid '.$brand.'; padding-bottom: 14px; margin-bottom: 16px;' }} }
  .a4 td.items { border-bottom: 1px solid #eee; }
</style></head>
<body>
@if($ticket)
  <div class="c head">
    @if($logo)<img src="{{ $logo }}" style="max-width:60px;max-height:60px;margin-bottom:4px"><br>@endif
    <div class="b" style="font-size:13px">{{ $tenant->name }}</div>
    @if($tenant->receipt_header)<div>{{ $tenant->receipt_header }}</div>@endif
    <div class="m">{{ $sale->branch->address ?? $tenant->address }} {{ $tenant->city }}</div>
    @if($tenant->phone)<div class="m">Tél : {{ $tenant->phone }}</div>@endif
    @if($tenant->tax_id)<div class="m">NIF/RCCM : {{ $tenant->tax_id }}</div>@endif
  </div>
  <div class="dash"></div>
  <table>
    <tr><td>Reçu</td><td class="r b">{{ $sale->number }}</td></tr>
    <tr><td>Date</td><td class="r">{{ $sale->sold_at->format('d/m/Y H:i') }}</td></tr>
    @if($sale->user)<tr><td>Vendeur</td><td class="r">{{ $sale->user->name }}</td></tr>@endif
    @if($sale->customer_name)<tr><td>Client</td><td class="r">{{ $sale->customer_name }}</td></tr>@endif
  </table>
  @if($sale->status === 'cancelled')<div class="c b" style="font-size:12px;margin:6px 0">*** VENTE ANNULÉE ***</div>@endif
  <div class="dash"></div>
  <table>
    @foreach($sale->items as $it)
      <tr><td colspan="2" class="b">{{ $it->name }}</td></tr>
      <tr><td class="m">{{ $q($it->quantity) }} x {{ $fmt($it->unit_price) }}</td><td class="r">{{ $fmt($it->total) }}</td></tr>
    @endforeach
  </table>
  <div class="dash"></div>
  <table>
    @if($sale->discount)<tr><td>Remise</td><td class="r">- {{ $fmt($sale->discount) }}</td></tr>@endif
    <tr class="tot"><td>TOTAL</td><td class="r">{{ $fmt($sale->total) }}</td></tr>
    <tr><td>Payé ({{ $sale->paymentLabel() }})</td><td class="r">{{ $fmt($sale->paid) }}</td></tr>
    @if($sale->change())<tr><td>Monnaie</td><td class="r">{{ $fmt($sale->change()) }}</td></tr>@endif
    @if($sale->due())<tr><td class="b">Reste dû</td><td class="r b">{{ $fmt($sale->due()) }}</td></tr>@endif
  </table>
  <div class="dash"></div>
  <div class="c">{{ $tenant->receipt_footer ?: 'Merci pour votre achat !' }}</div>
  <div class="c m" style="font-size:7px;margin-top:4px">Vérifier ce reçu : {{ $qr }}</div>
@else
  <table class="head"><tr>
    <td style="width:60%">
      @if($logo)<img src="{{ $logo }}" style="max-height:70px;max-width:160px;margin-bottom:6px"><br>@endif
      <div class="b" style="font-size:18px">{{ $tenant->name }}</div>
      @if($tenant->receipt_header)<div>{{ $tenant->receipt_header }}</div>@endif
      <div class="m">{{ $sale->branch->address ?? $tenant->address }} {{ $tenant->city }}</div>
      @if($tenant->phone)<div class="m">Tél : {{ $tenant->phone }}</div>@endif
      @if($tenant->tax_id)<div class="m">NIF/RCCM : {{ $tenant->tax_id }}</div>@endif
    </td>
    <td class="r">
      <div class="b brand" style="font-size:22px">REÇU DE VENTE</div>
      <div class="b" style="font-size:13px">{{ $sale->number }}</div>
      <div class="m">{{ $sale->sold_at->format('d/m/Y à H:i') }}</div>
      @if($sale->status === 'cancelled')<div class="b" style="color:#c00">VENTE ANNULÉE</div>@endif
    </td>
  </tr></table>
  @if($sale->customer_name || $sale->customer_phone)
    <div style="margin-bottom:14px"><span class="m">Client :</span> <b>{{ $sale->customer_name }}</b> {{ $sale->customer_phone }}</div>
  @endif
  <table class="a4">
    <thead><tr><th>Article</th><th class="r">Qté</th><th class="r">Prix unitaire</th><th class="r">Total</th></tr></thead>
    <tbody>
    @foreach($sale->items as $it)
      <tr><td class="items">{{ $it->name }}</td><td class="items r">{{ $q($it->quantity) }}</td><td class="items r">{{ $fmt($it->unit_price) }}</td><td class="items r b">{{ $fmt($it->total) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  <table style="width:45%;margin-left:55%;margin-top:14px">
    <tr><td class="m">Sous-total</td><td class="r">{{ $fmt($sale->subtotal) }}</td></tr>
    @if($sale->discount)<tr><td class="m">Remise</td><td class="r">- {{ $fmt($sale->discount) }}</td></tr>@endif
    <tr class="tot"><td>TOTAL</td><td class="r brand">{{ $fmt($sale->total) }}</td></tr>
    <tr><td class="m">Payé ({{ $sale->paymentLabel() }})</td><td class="r">{{ $fmt($sale->paid) }}</td></tr>
    @if($sale->due())<tr><td class="b">Reste à payer</td><td class="r b">{{ $fmt($sale->due()) }}</td></tr>@endif
  </table>
  <div style="margin-top:40px" class="c">{{ $tenant->receipt_footer ?: 'Merci pour votre achat !' }}</div>
  <div class="c m" style="margin-top:6px;font-size:9px">Vendeur : {{ $sale->user->name ?? '—' }} · Vérifier ce reçu en ligne : {{ $qr }}</div>
@endif
</body></html>
