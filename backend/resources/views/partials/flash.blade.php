@if(session('success'))
  <div class="alert alert-ok" x-data="{s:true}" x-show="s" x-init="setTimeout(()=>s=false,6000)"><x-icon name="check"/> <div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
  <div class="alert alert-err"><x-icon name="alert"/>
    <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  </div>
@endif
