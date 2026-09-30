@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@section('content')
<section>
	<div class="container-fluid">
        <div class="card">
            <div class="card-body">
				<div class="col-md-12">
					<div class="col-md-6 offset-md-3 mt-3 text-center">
						<form action="{{ route('report.warehouseStock') }}" method="GET" id="report-form">
						<h3>{{__('db.Stock Chart')}} </h3>
						<p>Select warehouse to view chart</p>
						<select class="form-control mb-3" id="warehouse_id" name="warehouse_id">
							<option value="0">{{__('db.All Warehouse')}}</option>
							@foreach($lims_warehouse_list as $warehouse)
							<option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
							@endforeach
						</select>
						</form>
					</div>

					<div id="warehouse-content">
						@include('backend.report.partials.warehouse_stock_table')
					</div>
				</div>
			</div>
		</div>
	</div>
</section>


@endsection

@push('scripts')

    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/chart.js/Chart.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset($asset_prefix . 'js/charts-custom.js') }}"></script>
	<script type="text/javascript">
		$("ul#report").siblings('a').attr('aria-expanded','true');
		$("ul#report").addClass("show");
		$("ul#report #warehouse-stock-report-menu").addClass("active");
		var warehouse_id = {{ json_encode($warehouse_id) }};
		$('#warehouse_id').val(warehouse_id);
		$('.selectpicker').selectpicker('refresh');

		function initializePieChart() {
			var PIECHART = $('#pieChart');
			if (PIECHART.length > 0) {
				var brandPrimary = PIECHART.data('color');
				var brandPrimaryRgba = PIECHART.data('color_rgba');
				var price = PIECHART.data('price');
				var cost = PIECHART.data('cost');
				var label1 = PIECHART.data('label1');
				var label2 = PIECHART.data('label2');
				var label3 = PIECHART.data('label3');
				var myPieChart = new Chart(PIECHART, {
					type: 'pie',
					data: {
						labels: [
							label1,
							label2,
							label3
						],
						datasets: [
							{
								data: [price, cost, price - cost],
								borderWidth: [1, 1, 1],
								backgroundColor: [
									brandPrimary,
									"#ff8952",
									"#858c85"
								],
								hoverBackgroundColor: [
									brandPrimaryRgba,
									"rgba(255, 137, 82, 0.8)",
									"rgb(133, 140, 133, 0.8)"
								],
								hoverBorderWidth: [4, 4, 4],
								hoverBorderColor: [
									brandPrimaryRgba,
									"rgba(255, 137, 82, 0.8)",
									"rgb(133, 140, 133, 0.8)",
								],
							}]
					},
					options: {}
				});
			}
		}

		initializePieChart();

		function reloadWarehouseStock() {
			var warehouse_id = $('#warehouse_id').val();
			$.ajax({
				url: "{{ route('report.warehouseStock') }}",
				type: "GET",
				data: {
					warehouse_id: warehouse_id
				},
				success: function(data) {
					$('#warehouse-content').html(data);
					initializePieChart();
				}
			});
		}

		$('#warehouse_id').on("change", function(){
			reloadWarehouseStock();
		});
	</script>
@endpush
