@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <a href="{{route('discounts.create')}}" class="btn btn-info"><i class="ti ti-plus"></i> {{__('db.Create Discount')}}</a>&nbsp;
    </div>
    <div class="table-responsive">
        <table id="discount-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.name')}}</th>
                    <th>{{__('db.Value')}}</th>
                    <th>{{__('db.Discount Plan')}}</th>
                    <th>{{__('db.Validity')}}</th>
                    <th>{{__('db.Days')}}</th>
                    <th>{{__('db.Products')}}</th>
                    <th>{{__('db.status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_discount_all as $key=> $discount)
                <tr data-id="{{$discount->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $discount->name }}</td>
                    <td>{{ $discount->value.' (' . $discount->type . ')' }}</td>
                    <td>
                        @foreach($discount->discountPlans as $index => $discount_plan)
                            @if($index)
                                {{', '.$discount_plan->name}}
                            @else
                                {{$discount_plan->name}}
                            @endif
                        @endforeach
                    </td>
                    <td>{{date(gen_setting()->date_format, strtotime($discount->valid_from)).'-'.date(gen_setting()->date_format, strtotime($discount->valid_till))}}</td>
                    <td>{{ $discount->days }}</td>
                    <td>
                        @if($discount->product_list)
                            <?php $products = \App\Models\Product::select('name', 'code')->whereIn('id', explode(",", $discount->product_list))->get(); ?>
                            @foreach($products as $index => $product)
                                @if($index)
                                    {{', '.$product->name.'['.$product->code.']'}}
                                @else
                                    {{$product->name.'['.$product->code.']'}}
                                @endif
                            @endforeach
                        @else
                            {{__('db.All Products')}}
                        @endif
                    </td>
                    @if($discount->is_active)
                        <td>{{ __('db.Active')}}</td>
                    @else
                        <td>{{ __('db.Inactive')}}</td>
                    @endif
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <a href="{{ route('discounts.edit', $discount->id) }}" class="btn btn-link"><i class="ti ti-edit"></i> {{__('db.edit')}}</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

</form>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #discount-list-menu").addClass("active");

    var biller_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }
    var table = $('#discount-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
             "info":      '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{__("db.Search")}}',
            'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 2, 3, 4]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                }
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="ti ti-eye"></i>',
                columns: ':gt(0)'
            },
        ],
    } );

</script>
@endpush
