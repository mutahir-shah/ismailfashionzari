@extends('backend.layout.main')
@section('content')
<section>
	<div class="container-fluid">
		<div class="card">
			<div class="card-body">
				<form action="{{ route('report.dailyPurchaseByWarehouse', ['year' => $year, 'month' => $month]) }}" method="post" id="report-form">
					@csrf
					<input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}">
					<h4 class="text-center">{{__('db.Daily Purchase Report')}} &nbsp;&nbsp;
					<select class="selectpicker" id="warehouse_id" name="warehouse_id">
						<option value="0">{{__('db.All Warehouse')}}</option>
						@foreach($lims_warehouse_list as $warehouse)
					<option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
					@endforeach
				</select>
				</h4>
				<div id="report-table">
					@include('backend.report.partials.daily_purchase_table')
				</div>
			</div>
		</div>
	</div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">

	$("ul#report").siblings('a').attr('aria-expanded','true');
    $("ul#report").addClass("show");
    $("ul#report #daily-purchase-report-menu").addClass("active");

	$('#warehouse_id').val($('input[name="warehouse_id_hidden"]').val());
	$('.selectpicker').selectpicker('refresh');

	$('#warehouse_id').on("change", function(){
		let warehouse_id = $(this).val();

		$.ajax({
			url: "{{ route('report.dailyPurchaseByWarehouse', ['year' => $year, 'month' => $month]) }}",
			method: "POST",
			data: {
				_token: "{{ csrf_token() }}",
				warehouse_id: warehouse_id
			},
			beforeSend: function () {
				$('#report-table').html('<div class="text-center">Loading...</div>');
			},
			success: function (response) {
				$('#report-table').html(response);
			}
		});
	});
</script>
@endpush
