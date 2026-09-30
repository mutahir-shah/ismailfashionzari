@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="{{ route('damage-stock.create') }}" class="btn btn-info">
            <i class="ti ti-plus"></i> {{ __('db.Add Damage Stock') }}
        </a>
    </div>
    <div class="table-responsive">
        <table id="damage-table" class="table damage-list">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.date') }}</th>
                    <th>{{ __('db.reference') }}</th>
                    <th>{{ __('db.Warehouse') }}</th>
                    <th>{{ __('db.product') }}s</th>
                    <th>{{ __('db.note') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_damage_all as $key => $damage)
                <tr data-id="{{ $damage->id }}">
                    <td>{{ $key }}</td>
                    <td>{{ date('d-m-Y', strtotime($damage->created_at->toDateString())) . ' ' . $damage->created_at->toTimeString() }}</td>
                    <td>{{ $damage->reference_no }}</td>
                    <?php $warehouse = DB::table('warehouses')->find($damage->warehouse_id) ?>
                    <td>{{ $warehouse->name }}</td>
                    <td>
                        <?php
                            $product_damage_data = DB::table('product_damage_stocks')->where('damage_stock_id', $damage->id)->get();
                            foreach ($product_damage_data as $key => $product_damage) {
                                if ($product_damage->variant_id) {
                                    $product = DB::table('products')
                                        ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                                        ->select('products.name', 'product_variants.item_code as code')
                                        ->where([
                                            ['product_id', $product_damage->product_id],
                                            ['variant_id', $product_damage->variant_id]
                                        ])->first();
                                } else {
                                    $product = DB::table('products')->select('name', 'code')->find($product_damage->product_id);
                                }
                                if ($key) echo '<br>';
                                echo $product->name . '<br>' . $product_damage->qty . ' x ' . $product_damage->unit_cost;
                            }
                        ?>
                    </td>
                    <td>{{ $damage->note }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('db.action') }}<span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <a href="{{ route('damage-stock.edit', $damage->id) }}" class="btn btn-link">
                                        <i class="ti ti-edit"></i> {{ __('db.edit') }}
                                    </a>
                                </li>
                                <li class="divider"></li>
                                <form action="{{ route('damage-stock.destroy', $damage->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                            <i class="ti ti-trash"></i> {{ __('db.delete') }}
                                        </button>
                                    </li>
                                </form>
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    $("ul#product").siblings('a').attr('aria-expanded', 'true');
    $("ul#product").addClass("show");
    $("ul#product #damage-stock-menu").addClass("active");

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    var damage_id    = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var table = $('#damage-table').DataTable({
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{ __("db.records per page") }}',
            "info":       '<small>{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)</small>',
            "search":     '{{ __("db.Search") }}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next':     '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            { "orderable": false, 'targets': [0, 6] },
            { 'checkboxes': { 'selectRow': true }, 'targets': 0 }
        ],
        'select': { style: 'multi', selector: 'td:first-child' },
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                footer: true
            },
            {
                text: '<i title="delete" class="ti ti-x"></i>',
                className: 'buttons-delete',
                action: function (e, dt, node, config) {
                    if (user_verified == '1') {
                        damage_id.length = 0;
                        $(':checkbox:checked').each(function (i) {
                            if (i) {
                                damage_id[i - 1] = $(this).closest('tr').data('id');
                            }
                        });
                        if (damage_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type: 'POST',
                                url: 'damage-stock/deletebyselection',
                                data: { damageIdArray: damage_id },
                                success: function (data) { alert(data); }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        } else if (!damage_id.length) {
                            alert('Nothing is selected!');
                        }
                    } else {
                        alert('This feature is disable for demo!');
                    }
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    });
</script>
@endpush
