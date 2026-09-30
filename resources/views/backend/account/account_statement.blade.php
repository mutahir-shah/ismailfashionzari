@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <x-success-message key="message" />

    <section class="forms">
        <div class="container-fluid">
            <h3>{{ __('db.Account Statement') }}</h3>
            <strong>{{ __('db.Account') }}:</strong> {{ $lims_account_data->name }} [{{ $lims_account_data->account_no }}]
        </div>

        <div class="table-responsive mb-4">
            <table id="account-table" class="table table-hover">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.date') }}</th>
                        <th>{{ __('db.Reference No') }}</th>
                        <th>{{ __('db.Related Transaction') }}</th>
                        <th>{{ __('db.Credit') }}</th>
                        <th>{{ __('db.Debit') }}</th>
                        <th>{{ __('db.Balance') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $balance = $opening_balance ?? $initial_balance->initial_balance;
                        $total_credit = 0;
                        $total_debit = 0;
                        $total_debit = 0;
                    @endphp


                  
                    @forelse(array_reverse($final_array) as $key => $data)
                        <tr>
                            <td>{{ $key + 1 }}</td>

                            <td data-sort="{{ $data[0] ? date('Y-m-d', strtotime($data[0])) : '' }}">
                                {{ $data[0] ? date(gen_setting()->date_format, strtotime($data[0])) : '' }}
                            </td>

                            <td>{{ $data[1] }}</td>
                            <td>{{ $data[2] }}</td>

                            <td>{{ number_format($data[3], gen_setting()->decimal, '.', '') }}</td>
                            <td>{{ number_format($data[4], gen_setting()->decimal, '.', '') }}</td>
                            <td>{{ number_format($data[5], gen_setting()->decimal, '.', '') }}</td>
                        </tr>

                        @if ($loop->last)
                            <tr>
                            <td>{{ $key + 1 }}</td>

                            <td data-sort="{{ $data[0] ? date('Y-m-d', strtotime($initial_balance->created_at)) : '' }}">
                                {{ $data[0] ? date(gen_setting()->date_format, strtotime($initial_balance->created_at)) : '' }}
                            </td>

                            <td>Opening Balance</td>
                            <td>------</td>

                            <td>{{ number_format($opening_balance ?? $initial_balance->initial_balance, gen_setting()->decimal, '.', '') }}</td>
                            <td>0</td>
                            <td>{{ number_format($opening_balance ?? $initial_balance->initial_balance, gen_setting()->decimal, '.', '') }}</td>
                        </tr>
                        @endif
                    @empty
                        @if (($opening_balance ?? $initial_balance->initial_balance) != 0)
                            <tr>
                            <td>1</td>

                            <td data-sort="{{ $initial_balance->created_at ? date('Y-m-d', strtotime($initial_balance->created_at)) : '' }}">
                                {{ $initial_balance->created_at ? date(gen_setting()->date_format, strtotime($initial_balance->created_at)) : '' }}
                            </td>

                            <td>Opening Balance</td>
                            <td>------</td>

                            <td>{{ number_format($opening_balance ?? $initial_balance->initial_balance, gen_setting()->decimal, '.', '') }}</td>
                            <td>0</td>
                            <td>{{ number_format($opening_balance ?? $initial_balance->initial_balance, gen_setting()->decimal, '.', '') }}</td>
                        </tr>
                        @endif
                    @endforelse

                </tbody>
            </table>
        </div>


    </section>
@endsection


@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">

        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "extract-date-pre": function(value) {
                var date = $(value, 'span')[0].innerHTML;
                date = date.split('/');
                return Date.parse(date[1] + '/' + date[0] + '/' + date[2])
            },
            "extract-date-asc": function(a, b) {
                return ((a < b) ? -1 : ((a > b) ? 1 : 0));
            },
            "extract-date-desc": function(a, b) {
                return ((a < b) ? 1 : ((a > b) ? -1 : 0));
            }
        });

        $('#account-table').DataTable({
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
                    type: 'extract-date',
                    'targets': 0
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
                    text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="ti ti-printer"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible'
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
