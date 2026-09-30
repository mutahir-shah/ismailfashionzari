@props(['key'])

@if(session()->has($key))
  <div class="alert alert-warning alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get($key) }}</div>
@endif