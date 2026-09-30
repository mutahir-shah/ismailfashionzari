@extends('backend.layout.main')
@section('content')
<style type="text/css">
    .top-fields{margin-top:10px;position: relative;}
    .top-fields label {font-size:11px;font-weight:600;margin-left:10px;padding:0 3px;position:absolute;top:-8px;z-index:9;}
    .top-fields input{font-size:13px;height:45px}
</style>
<section>
    <form id="profitLossForm">
        @csrf
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-center">{{__('db.profit_loss_report')}}</h3>
                    <div class="row mt-4 justify-content-center">
                        <!-- Warehouse Dropdown -->
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.Warehouse')}}</label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{__('db.All Warehouse')}}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}" {{ isset($lims_warehouse) && @$lims_warehouse->id == $warehouse->id ? 'selected' : '' }}>
                                            {{$warehouse->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date Range Picker -->
                        <div class="col-md-3">
                            <div class="form-group top-fields">
                                <label>{{__('db.Choose Your Date')}}</label>
                                <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                <input type="hidden" name="start_date" value="" />
                                <input type="hidden" name="end_date" value="" />
                            </div>

                        </div>

                        <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                            <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                            <span>Loading results...</span>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div id="profitLossResult">
            @if(isset($purchase))
                @include('report.profit_loss_result')
            @endif
        </div>
    </form>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("profitLossForm");
    const warehouse = document.getElementById("warehouse_id");
    const loading = document.getElementById("filter-loading");
    const resultBox = document.getElementById("profitLossResult");

    function submitFilter() {

        let formData = new FormData(form);

        // Show loading
        loading.style.display = "block";
        resultBox.style.opacity = "0.4";

        fetch("{{ route('report.profitLoss') }}", {
            method: "POST",
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            resultBox.innerHTML = html;
        })
        .catch(error => {
            console.error(error);
        })
        .finally(() => {
            loading.style.display = "none";
            resultBox.style.opacity = "1";
        });
    }

    $(document).ready(function() {
        submitFilter();
    })

    // 🔹 Warehouse Change (Bootstrap Select Compatible)
    $('#warehouse_id').on('changed.bs.select', function () {
        submitFilter();
    });

    // 🔹 Date Range Apply
    $('.daterangepicker-field').on('apply.daterangepicker', function(ev, picker) {

        $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));

        submitFilter();
    });

});
</script>
@endpush
