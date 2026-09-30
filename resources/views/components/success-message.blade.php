@props(['key'])

@if(session()->has($key))
<div class="container-fluid">
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get($key) !!}</div>
</div>
@endif