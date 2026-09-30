@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush
 @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="card">
        <div class="card-header mt-2">
            <h3 class="text-center">{{__('db.Notification List')}}</h3>
        </div>
    </div>
    <div class="table-responsive">
        <table id="notification-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.date')}}</th>
                    <th>{{__('db.From')}}</th>
                    <th>{{__('db.To')}}</th>
                    <th>{{__('db.Document')}}</th>
                    <th>{{__('db.Message')}}</th>
                    <th>{{__('db.Reminder Date')}}</th>
                    <th>{{__('db.status')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_notification_all as $key=>$notification)
                <?php 
                    $data = json_decode($notification->data);
                    $sender_id = $data->sender_id ?? null;
                    $receiver_id = $data->receiver_id ?? null;
                    $from_user = $sender_id ? \DB::table('users')->select('name')->where('id', $sender_id)->first() : null;
                    $to_user = $receiver_id ? \DB::table('users')->select('name')->where('id', $receiver_id)->first() : null;
                ?>
                <tr data-id="{{$notification->id}}">
                    <td>{{$key}}</td>
                    <td>{{ date(gen_setting()->date_format, strtotime($notification->created_at)) }}</td>
                    <td>{{$from_user->name ?? 'System'}}</td>
                    <td>{{$to_user->name ?? 'N/A'}}</td>
                    @if($data->document_name)
                    <td><a target="_blank" href="{{url('documents/notification', $data->document_name)}}">Open</a>
                    </td>
                    @else
                    <td>N/A</td>
                    @endif
                    <td>{{$data->message}}</td>
                    @if(isset($data->reminder_date))
                    <td>{{ date(gen_setting()->date_format, strtotime($data->reminder_date)) }}</td>
                    @else
                    <td>N/A</td>
                    @endif
                    @if($notification->read_at)
                        <td><div class="badge badge-success">{{__('db.Read')}}</div></td>
                    @else
                        <td><div class="badge badge-danger">{{__('db.Unread')}}</div></td>
                    @endif
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

    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #notification-list-menu").addClass("active");

    var brand_id = [];
    var user_verified = <?php echo json_encode(config('app.user_verified'))?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $( "#select_all" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("tbody input[type='checkbox']").prop('checked', true);
        }
        else {
            $("tbody input[type='checkbox']").prop('checked', false);
        }
    });

    $('#notification-table').DataTable( {
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
                'targets': [0, 1, 3]
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
                    rows: ':visible',
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
