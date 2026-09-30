@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main') @section('content')
<div class="container-fluid">
	<div class="row">
		<div class="col-md-12">
			<form action="{{ route('report.bestSellerByWarehouse') }}" method="post" id="report-form">
				@csrf
				<input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}">
                <h4 class="text-center mt-3">{{__('db.Best Seller')}} {{__('db.From')}} {{$start_month.' - '.date("F Y")}} &nbsp;&nbsp;
                <select class="selectpicker" id="warehouse_id" name="warehouse_id">
                    <option value="0">{{__('db.All Warehouse')}}</option>
                    @foreach($lims_warehouse_list as $warehouse)
                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                    @endforeach
                </select>
                </h4>
            </form>
            <div class="card mt-3">
                <div class="card-body">
                    @php
                        if(gen_setting()->theme == 'default.css'){
                            $color = '#733686';
                            $color_rgba = 'rgba(115, 54, 134, 0.8)';
                        }
                        elseif(gen_setting()->theme == 'green.css'){
                            $color = '#2ecc71';
                            $color_rgba = 'rgba(46, 204, 113, 0.8)';
                        }
                        elseif(gen_setting()->theme == 'blue.css'){
                            $color = '#3498db';
                            $color_rgba = 'rgba(52, 152, 219, 0.8)';
                        }
                        elseif(gen_setting()->theme == 'dark.css'){
                            $color = '#34495e';
                            $color_rgba = 'rgba(52, 73, 94, 0.8)';
                        }
                    @endphp
                    <canvas id="bestSeller" data-color="{{$color}}" data-color_rgba="{{$color_rgba}}" data-product = "{{json_encode($product)}}" data-sold_qty="{{json_encode($sold_qty)}}" ></canvas>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection
@push('scripts')

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/chart.js/Chart.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'js/charts-custom.js') }}"></script>

@endpush
