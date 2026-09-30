@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        @if (!empty($asset_id))
        <div class="container-fluid">
            <a href="https://business.facebook.com/latest/whatsapp_manager/message_templates?asset_id={{ $asset_id }}" target="_blank" class="btn btn-info">
            <i class="ti ti-plus"></i> {{ __('db.manage_template') }}</a>
        </div>
        @endif

        <div class="table-responsive">
            <table id="templates-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.name') }}</th>
                        <th>{{ __('db.language') }}</th>
                        <th>{{ __('db.category') }}</th>
                        <th>{{ __('db.status') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($templates as $key => $tpl)
                        <tr data-id="{{ $tpl['name'] }}">
                            <td>{{ $key }}</td>
                            <td>{{ $tpl['name'] }}</td>
                            <td>{{ $tpl['language'] }}</td>
                            <td>{{ $tpl['category'] ?? '' }}</td>
                            <td>{{ $tpl['status'] ?? '' }}</td>
                            <td>
                                <form action="{{ route('whatsapp.template.delete', $tpl['name']) }}" method="POST" onsubmit="return confirmDelete()">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i> {{ __('db.delete') }}</button>
                                </form>
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

        $('#templates-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
                "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{ __('db.Search') }}',
                'paginate': {
                    'previous': '<i class="ti ti-chevron-left"></i>',
                    'next': '<i class="ti ti-chevron-right"></i>'
                }
            },
            'columnDefs': [{
                    "orderable": false,
                    'targets': [0, 5]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
                            data =
                                '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
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
            'select': {
                style: 'multi',
                selector: 'td:first-child'
            },
            'lengthMenu': [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'csv',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    },
                },
                {
                    extend: 'colvis',
                    text: '<i title="" class="ti ti-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        });

    </script>
@endpush
