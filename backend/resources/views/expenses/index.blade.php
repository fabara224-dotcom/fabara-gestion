@extends('layouts.app')
@section('title', 'Dépenses')
@section('content')
<div class="grid g-main" style="align-items:start">
  <div>
    <form class="row mb-2" method="GET"><input class="input" type="month" name="mois" value="{{ $month }}" style="width:auto" onchange="this.form.submit()"></form>
    <div class="grid g-2 mb-3">
      <div class="card stat"><div class="ico bg-red"><x-icon name="wallet"/></div><div class="lbl">Total des dépenses du mois</div><div class="val">@money($total)</div></div>
      <div class="card">
        @forelse($byCategory as $k => $v)<div class="row between small"><span>{{ $categories[$k] ?? $k }}</span><b>@money($v)</b></div>@empty<div class="muted small">Aucune dépense ce mois-ci.</div>@endforelse
      </div>
    </div>
    <div class="card p0"><div class="table-wrap"><table class="table">
      <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Par</th><th class="right">Montant</th><th></th></tr></thead>
      <tbody>
      @forelse($expenses as $e)
        <tr>
          <td class="small">{{ $e->spent_on->format('d/m/Y') }}</td><td class="bold">{{ $e->label }}</td>
          <td><span class="badge b-gray">{{ $categories[$e->category] ?? $e->category }}</span></td>
          <td class="small muted">{{ $e->user->name ?? '—' }}</td><td class="right bold">@money($e->amount)</td>
          <td class="right"><form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm"><x-icon name="trash" width="15" height="15"/></button></form></td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty"><div class="ill"><x-icon name="wallet"/></div>Notez loyer, transport, salaires… pour connaître votre vrai bénéfice.</div></td></tr>
      @endforelse
      </tbody>
    </table></div></div>
    {{ $expenses->links() }}
  </div>
  <div class="card" style="position:sticky;top:90px">
    <h3 class="bold mb-2" style="font-size:17px">Nouvelle dépense</h3>
    <form method="POST" action="{{ route('expenses.store') }}">@csrf
      <div class="field"><label class="lbl">Libellé</label><input class="input" name="label" required placeholder="Ex : Loyer novembre"></div>
      <div class="field"><label class="lbl">Catégorie</label><select class="input" name="category">@foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
      <div class="grid g-2" style="gap:10px">
        <div class="field"><label class="lbl">Montant</label><input class="input" type="number" min="1" name="amount" required></div>
        <div class="field"><label class="lbl">Date</label><input class="input" type="date" name="spent_on" value="{{ today()->toDateString() }}" required></div>
      </div>
      <button class="btn btn-primary btn-block btn-lg">Enregistrer</button>
    </form>
  </div>
</div>
@endsection
