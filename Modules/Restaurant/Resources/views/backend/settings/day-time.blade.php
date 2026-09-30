@extends('backend.layout.main') @section('content')

@push('css')
<style>
    .change-theme-color {
        align-items: center;
        cursor: pointer;
        display: flex;
        line-height:2
    }
    .change-theme-color span { 
        border-radius: 3px;
        height:15px;
        margin-right: 10px;
        width:15px;
    }
</style>
@endpush

@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{ __('db.days_times') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                        <form action="{{ route('setting.daytime.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                        <div class="row">
                            <div class="col-md-4">
                                {{ __('db.day') }}
                            </div>
                            <div class="col-md-4">
                                {{ __('db.start_time') }}
                            </div>
                            <div class="col-md-4">
                                {{ __('db.end_time') }}
                            </div>   
                        </div>   
                        @if(isset($day_times) && count($day_times) > 0)

                            @foreach($day_times as $day_time)
                            <hr>  
                                <div class="row">   
                                    <div class="col-md-4">
                                        <input type="text" name="day[]" class="form-control" value="{{$day_time->day}}">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="time" name="start_time[]" class="form-control" value="{{$day_time->start_time}}">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="time" name="end_time[]" class="form-control" value="{{$day_time->end_time}}">
                                    </div>  
                                </div>
                            <hr> 
                            @endforeach 

                        @else
                            @for($i = 0; $i < 7; $i++)
                            <hr>
                            <div class="row">   
                                <div class="col-md-4">
                                    <input type="text" name="day[]" class="form-control" value="">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="start_time[]" class="form-control" value="">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" name="end_time[]" class="form-control" value="">
                                </div>  
                            </div>                                                          
                            <hr>
                            @endfor
                        @endif

                        <div class="form-group">
                            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary">
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    $(document).on('click', '.change-theme-color', function(){
        var color = $(this).data('color');
        var def_color = '<span style="background-color:'+color+';width:15px;height:15px"></span> '+color;
        $('input[name=theme_color]').val(color);
        $('#def_color').html(def_color)
    })
</script>
@endpush