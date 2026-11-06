@extends('layouts.app')
@section('title', 'Caisse')
@section('content')
<div x-data="pos()" x-init="init()" @keydown.window.f2.prevent="$refs.search.focus()">
  <div class="pos">
    {{-- ===== Articles ===== --}}
    <div>
      <div class="row mb-2">
        <div class="search grow"><x-icon name="search"/>
          <input x-ref="search" class="input" x-model="q" @keydown.enter.prevent="scan()" placeholder="Rechercher un article ou scanner un code-barres (F2)">
        </div>
      </div>
      <div class="chips mb-3">
        <button class="chip" :class="!cat && 'on'" @click="cat=null">Tout</button>
        @foreach($categories as $c)
          <button class="chip" :class="cat==='{{ $c->id }}' && 'on'" @click="cat='{{ $c->id }}'"><span style="width:9px;height:9px;border-radius:50%;background:{{ $c->color ?? '#ccc' }}"></span>{{ $c->name }}</button>
        @endforeach
      </div>

      <div class="pgrid">
        <template x-for="p in filtered" :key="p.id">
          <button type="button" class="pcard" @click="add(p)">
            <div class="img">
              <template x-if="p.image"><img :src="p.image" loading="lazy" alt=""></template>
              <template x-if="!p.image"><span x-text="p.name.substring(0,2).toUpperCase()"></span></template>
            </div>
            <span class="q" x-show="qtyOf(p.id)" x-text="qtyOf(p.id)"></span>
            <span class="s badge" x-show="p.track" :class="p.stock<=0 ? 'b-red' : (p.stock<=3 ? 'b-yellow':'b-gray')" x-text="fmtQ(p.stock)"></span>
            <div class="n" x-text="p.name"></div>
            <div class="p" x-text="money(p.price)"></div>
          </button>
        </template>
      </div>
      <div class="empty" x-show="!filtered.length" x-cloak>
        <div class="ill"><x-icon name="box"/></div>Aucun article trouvé.
        @if(auth()->user()->canManage())<div class="mt-2"><a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/> Ajouter un article</a></div>@endif
      </div>
    </div>

    {{-- ===== Panier ===== --}}
    <div class="card cart" :class="open && 'open'">
      <div class="card-h"><h3>🧾 Vente en cours</h3>
        <div class="row" style="gap:6px"><button class="btn btn-ghost btn-sm" x-show="cart.length" @click="clear()">Vider</button>
        <button class="icon-btn mobile-only" style="display:none" @click="open=false"><x-icon name="x"/></button></div>
      </div>

      <div class="lines">
        <template x-for="l in cart" :key="l.id">
          <div class="cline">
            <div class="grow"><div class="bold small" x-text="l.name"></div><div class="xs muted" x-text="money(l.price)+' / '+l.unit"></div></div>
            <div class="qty"><button @click="dec(l)">−</button><input type="number" step="any" min="0" x-model.number="l.qty"><button @click="l.qty++">+</button></div>
            <div class="bold small right" style="width:96px" x-text="money(l.price*l.qty)"></div>
          </div>
        </template>
        <div class="empty" x-show="!cart.length" style="padding:40px 10px"><div class="ill"><x-icon name="cart"/></div>Touchez un article pour l'ajouter.</div>
      </div>

      <div x-show="cart.length" x-cloak style="border-top:1px solid var(--line);padding-top:14px">
        <div class="row between small"><span class="muted">Sous-total</span><span x-text="money(subtotal)"></span></div>
        <div class="row between small mt-1"><span class="muted">Remise</span><input class="input sm" style="width:130px;text-align:right" type="number" min="0" x-model.number="discount" placeholder="0"></div>
        <div class="total mt-2"><span>Total</span><span class="t-red" x-text="money(total)"></span></div>

        <div class="pay mt-2">
          @foreach($methods as $k => $v)
            <label><input type="radio" name="pm" value="{{ $k }}" x-model="method"><span>{{ $v }}</span></label>
          @endforeach
        </div>

        <div class="grid g-2 mt-2" style="gap:8px">
          <div><label class="lbl xs">Montant reçu</label><input class="input sm" type="number" min="0" x-model.number="paid" :placeholder="total"></div>
          <div><label class="lbl xs" x-text="method==='credit' ? 'Reste dû' : 'Monnaie à rendre'"></label>
            <div class="input sm bold" style="display:flex;align-items:center" :class="method==='credit' ? 't-red' : 't-green'" x-text="money(method==='credit' ? Math.max(0,total-(paid||0)) : Math.max(0,(paid||total)-total))"></div></div>
        </div>
        <details class="mt-2" :open="method==='credit'">
          <summary class="small bold" style="cursor:pointer">Client (pour le reçu WhatsApp / crédit)</summary>
          <div class="grid g-2 mt-1" style="gap:8px">
            <input class="input sm" x-model="customer_name" placeholder="Nom">
            <input class="input sm" x-model="customer_phone" placeholder="Téléphone">
          </div>
        </details>
        <div class="err" x-text="error" x-show="error"></div>
        <button class="btn btn-primary btn-lg btn-block mt-2" :disabled="busy" @click="checkout()">
          <x-icon name="check"/> <span x-text="busy ? 'Enregistrement…' : 'Encaisser '+money(total)"></span>
        </button>
      </div>
    </div>
  </div>

  <button class="btn btn-primary btn-lg mobile-cart-btn" x-show="cart.length && !open" x-cloak @click="open=true">
    <x-icon name="cart"/> <span x-text="count+' article(s) · '+money(total)"></span>
  </button>
</div>
@endsection

@push('scripts')
<style>@media(max-width:1100px){.cart .mobile-only{display:grid!important}}</style>
<script>
function pos(){
  return {
    products: @json($products), q:'', cat:null, cart:[], discount:0, paid:null, method:'cash',
    customer_name:'', customer_phone:'', busy:false, error:'', open:false,
    init(){ try{ const s = JSON.parse(localStorage.getItem('pos_cart')||'[]'); if(Array.isArray(s)) this.cart = s; }catch(e){}
            this.$watch('cart', v => { try{ localStorage.setItem('pos_cart', JSON.stringify(v)) }catch(e){} }); },
    get filtered(){ const q=this.q.trim().toLowerCase();
      return this.products.filter(p => (!this.cat || p.category_id===this.cat) && (!q || p.name.toLowerCase().includes(q) || (p.barcode||'')===q)); },
    get subtotal(){ return this.cart.reduce((s,l)=>s+Math.round(l.price*(l.qty||0)),0) },
    get total(){ return Math.max(0, this.subtotal-(this.discount||0)) },
    get count(){ return this.cart.reduce((s,l)=>s+(+l.qty||0),0) },
    qtyOf(id){ const l=this.cart.find(x=>x.id===id); return l ? this.fmtQ(l.qty) : 0 },
    add(p){ const l=this.cart.find(x=>x.id===p.id); if(l) l.qty++; else this.cart.push({id:p.id,name:p.name,price:p.price,unit:p.unit,qty:1}); },
    dec(l){ l.qty--; if(l.qty<=0) this.cart=this.cart.filter(x=>x!==l); },
    clear(){ this.cart=[]; this.discount=0; this.paid=null; this.customer_name=''; this.customer_phone=''; },
    scan(){ const p=this.products.find(p=>p.barcode && p.barcode===this.q.trim()) || (this.filtered.length===1 ? this.filtered[0] : null); if(p){ this.add(p); this.q=''; } },
    money(n){ return Math.round(n||0).toLocaleString('fr-FR').replace(/ | /g,' ')+' {{ $currentTenant->currency }}' },
    fmtQ(n){ return (+n % 1 === 0) ? (+n).toString() : (+n).toFixed(2) },
    async checkout(){
      this.error=''; this.busy=true;
      try{
        const r = await fetch('{{ route('pos.store') }}', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
          body: JSON.stringify({ items:this.cart.filter(l=>l.qty>0).map(l=>({product_id:l.id, quantity:l.qty})), discount:this.discount||0,
            paid: this.paid ?? (this.method==='credit' ? 0 : this.total), payment_method:this.method,
            customer_name:this.customer_name||null, customer_phone:this.customer_phone||null })});
        const d = await r.json();
        if(!r.ok){ this.error = d.message || 'Erreur, réessayez.'; this.busy=false; return; }
        this.cart=[]; try{ localStorage.removeItem('pos_cart') }catch(e){}
        location = d.redirect;
      }catch(e){ this.error='Connexion impossible. Utilisez l\'application mobile en mode hors ligne.'; this.busy=false; }
    }
  }
}
</script>
@endpush
