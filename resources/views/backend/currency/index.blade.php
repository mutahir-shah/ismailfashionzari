@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-validation-error fieldName="name" />
<x-validation-error fieldName="code" />
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="ti ti-plus"></i> {{__('db.Add Currency')}} </button>&nbsp;
    </div>
    <div class="table-responsive">
        <table id="currency-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Currency Name')}}</th>
                    <th>{{__('db.Currency Code')}}</th>
                    <th>{{__('db.symbol')}}</th>
                    <th>{{__('db.Exchange Rate')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_currency_all as $key=>$currency_data)
                <tr data-id="{{$currency_data->id}}">
                    <td>{{$key}}</td>
                    <td>{{ $currency_data->name }}</td>
                    <td>{{ $currency_data->code }}</td>
                    <td>{{ $currency_data->symbol }}</td>
                    <td>{{ $currency_data->exchange_rate }}</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{__('db.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li><button type="button" data-id="{{$currency_data->id}}" data-name="{{$currency_data->name}}" data-code="{{$currency_data->code}}" data-exchange_rate="{{$currency_data->exchange_rate}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal"><i class="ti ti-edit"></i> {{__('db.edit')}}</button></li>
                                @if($currency_data->exchange_rate != 1)
                                <li class="divider"></li>
                                <form action="{{ route('currency.destroy', $currency_data->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <li>
                                        <button type="submit" class="btn btn-link" onclick="return confirm('Are you sure want to delete?')"><i class="ti ti-trash"></i> {{__('db.delete')}}</button>
                                    </li>
                                </form>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('currency.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Add Currency') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>

                    <div class="form-group">
                        <label>{{ __('db.name') }} *</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type currency name') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Code') }} * <x-info title="USD, NGN, INR, PKR ..." type="info" /></label>
                        <input type="text" name="code" required class="form-control" placeholder="{{ __('db.Type currency code') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.symbol') }} <x-info title="$, ₹, ₦, € ..." type="info" /></label>
                        <input type="text" name="symbol" class="form-control" placeholder="{{ __('db.symbol') }}">
                    </div>

                    <div class="form-group">
                        <label>
                            {{ __('db.Exchange Rate') }} * <x-info title="{{ __('db.If this is your default currency, the exchange rate must be 1') }}" type="info" />
                        </label>
                        <input type="number" name="exchange_rate" required class="form-control" id="add_exchange_rate" min="0.0000001" step="any" placeholder="{{ __('db.Type exchange rate') }}">
                    </div>

                    <div class="form-group">
                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('currency.update', 1) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Update Currency') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>

                    <div class="form-group">
                        <label>{{ __('db.name') }} *</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('db.Type currency name') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Code') }} *</label>
                        <input type="text" name="code" required class="form-control" placeholder="{{ __('db.Type currency code') }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.symbol') }} <x-info title="$, ₹, ₦, € ..." type="info" /></label>
                        <input type="text" name="symbol" class="form-control" placeholder="{{ __('db.symbol') }}">
                    </div>

                    <div class="form-group">
                        <label>
                            {{ __('db.Exchange Rate') }} * 
                            <i class="ti ti-info-circle" data-toggle="tooltip" title="{{ __('db.If this is your default currency, the exchange rate must be 1') }}"></i>
                        </label>
                        <input type="number" name="exchange_rate" required class="form-control" id="edit_exchange_rate" min="0.0000001" step="any" placeholder="{{ __('db.Type exchange rate') }}">
                    </div>

                    <input type="hidden" name="currency_id">

                    <div class="form-group">
                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">

    $('#add_exchange_rate,#edit_exchange_rate').on('input',function(){
        var exchange_rate = $(this).val();
        var default_exchange_rate = {{$currency->exchange_rate}};
        if(exchange_rate == default_exchange_rate){
            var message = "{{__('db.Only default currency can have 1 as exchange rate. Please change the exchange rate of your default currency')}}";
            $(this).parent().append('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span>'+message+' - {{$currency->name}}</span></div>');
            $(this).closest('form').find(':input[type="submit"]').prop('disabled', true);
        }else{
            $(this).closest('form').find('.alert').remove();
            $(this).closest('form').find(':input[type="submit"]').prop('disabled', false);
        }
    });

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #currency-menu").addClass("active");

    $(document).ready(function() {
        $(document).on('click', '.edit-btn', function() {
            $("#editModal input[name='currency_id']").val($(this).data('id'));
            $("#editModal input[name='name']").val($(this).data('name'));
            $("#editModal input[name='code']").val($(this).data('code'));
            $("#editModal input[name='symbol']").val($(this).data('symbol'));
            $("#editModal input[name='exchange_rate']").val($(this).data('exchange_rate'));
        });
    });

    $('#currency-table').DataTable( {
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
                'targets': [0, 4]
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
                },
                customize: function(doc) {
                    for (var i = 1; i < doc.content[1].table.body.length; i++) {
                        if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                            var imagehtml = doc.content[1].table.body[i][0].text;
                            var regex = /<img.*?src=['"](.*?)['"]/;
                            var src = regex.exec(imagehtml)[1];
                            var tempImage = new Image();
                            tempImage.src = src;
                            var canvas = document.createElement("canvas");
                            canvas.width = tempImage.width;
                            canvas.height = tempImage.height;
                            var ctx = canvas.getContext("2d");
                            ctx.drawImage(tempImage, 0, 0);
                            var imagedata = canvas.toDataURL("image/png");
                            delete doc.content[1].table.body[i][0].text;
                            doc.content[1].table.body[i][0].image = imagedata;
                            doc.content[1].table.body[i][0].fit = [30, 30];
                        }
                    }
                },
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="ti ti-file-type-xls"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                data = regex.exec(data)[1];
                            }
                            return data;
                        }
                    }
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                data = regex.exec(data)[1];
                            }
                            return data;
                        }
                    }
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="ti ti-printer"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
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
