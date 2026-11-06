<div class="side">
  <a href="/" class="lp-logo" style="color:#fff;position:relative;z-index:1">
    <div class="stripes"><i style="background:#fff"></i><i style="background:#F4B400"></i><i style="background:#1B998B"></i><i style="background:#E84A8A"></i></div>
    {{ config('saas.name') }}
  </a>
  <div style="position:relative;z-index:1">
    <h2 style="font-size:38px;line-height:1.1;letter-spacing:-.03em;color:#fff">{{ $headline }}</h2>
    <p style="opacity:.8;font-size:17px;margin-top:14px;max-width:420px">{{ $text }}</p>
    <div class="grid mt-4" style="gap:12px;max-width:420px">
      @foreach(['Caisse et reçus en un clic', 'Stock avec photos', 'Marche sans internet', 'Chaque entreprise ne voit que ses données'] as $l)
        <div class="row" style="font-weight:600"><span style="width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.15);display:grid;place-items:center"><x-icon name="check" width="15" height="15"/></span>{{ $l }}</div>
      @endforeach
    </div>
  </div>
  <div style="opacity:.6;font-size:13px;position:relative;z-index:1">Besoin d'aide ? {{ config('saas.support_phone') }}</div>
</div>
