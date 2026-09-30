@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid mb-2">
        <button class="btn btn-info" data-toggle="modal" data-target="#createModal">
            <i class="ti ti-plus"></i> {{__('db.Add Holiday')}}
        </button>
    </div>

   <div class="table-responsive">
    <table id="holiday-table" class="table">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{ __('db.date') }}</th>
                <th>{{ __('db.Created By') }}</th>
                <th>{{ __('db.From') }}</th>
                <th>{{ __('db.To') }}</th>
                <th>{{ __('db.note') }}</th>
                <th>{{ __('db.Recurring') }}</th>
                <th>{{ __('db.Region') }}</th>
                <th class="not-exported">{{ __('db.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lims_holiday_list as $key => $holiday)
            <tr
                data-id="{{ $holiday->id }}"
                data-from="{{ date(gen_setting()->date_format, strtotime($holiday->from_date)) }}"
                data-to="{{ date(gen_setting()->date_format, strtotime($holiday->to_date)) }}"
                data-note="{{ $holiday->note }}"
                data-recurring="{{ $holiday->recurring }}"
                data-region="{{ $holiday->region }}"
            >
                <td>{{ $key + 1 }}</td>
                <td>{{ date(gen_setting()->date_format, strtotime($holiday->created_at)) }}</td>
                <td>{{ $holiday->user->name }}</td>
                <td>{{ date(gen_setting()->date_format, strtotime($holiday->from_date)) }}</td>
                <td>{{ date(gen_setting()->date_format, strtotime($holiday->to_date)) }}</td>
                <td>{{ $holiday->note }}</td>
                <td>{{ $holiday->recurring ? __('db.Yes') : __('db.No') }}</td>
                <td>{{ $holiday->region }}</td>
                <td>
                    <div class="btn-group">
                        @if(!$holiday->is_approved && $approve_permission)
                        <button type="button" class="btn btn-sm btn-success btn-approve" data-id="{{ $holiday->id }}" title="{{ __('db.Approve') }}">
                            <i class="ti ti-check"></i>
                        </button>
                        @endif

                        <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-menu"></i> {{ __('db.action') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <button type="button" class="btn btn-link btn-edit" data-toggle="modal" data-target="#editModal" title="{{ __('db.edit') }}">
                                    <i class="ti ti-edit"></i> {{ __('db.edit') }}
                                </button>
                            </li>
                            <li class="divider"></li>
                            <form action="{{ route('holidays.destroy', $holiday->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()" title="{{ __('db.delete') }}">
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

{{-- Create Modal --}}
<div id="createModal" class="modal fade text-left" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('holidays.store') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{__('db.Add Holiday')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>{{__('db.From')}} * <x-info title="Select the start date of the holiday" /></label>
                        <input type="text" name="from_date" class="form-control date" value="{{date('d-m-Y')}}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.To')}} * <x-info title="Select the end date of the holiday" /></label>
                        <input type="text" name="to_date" class="form-control date" value="{{date('d-m-Y')}}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Recurring')}} * <x-info title="Choose if this holiday repeats every year" /></label>
                        <select name="recurring" class="form-control" required>
                            <option value="0">{{__('db.No')}}</option>
                            <option value="1">{{__('db.Yes')}}</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Region')}} <x-info title="Specify the region this holiday applies to, if any" /></label>
                        <input type="text" name="region" class="form-control">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{__('db.note')}} <x-info title="Add any notes or description for this holiday" /></label>
                        <textarea name="note" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" class="modal fade text-left" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('holidays.update', 1) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('db.Update Holiday')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span><i class="ti ti-x"></i></span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.From')}} * <x-info title="Edit the start date of the holiday" /></label>
                            <input type="text" name="from_date" class="form-control date" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.To')}} * <x-info title="Edit the end date of the holiday" /></label>
                            <input type="text" name="to_date" class="form-control date" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Recurring')}} * <x-info title="Update if this holiday repeats every year" /></label>
                            <select name="recurring" class="form-control" required>
                                <option value="0">{{__('db.No')}}</option>
                                <option value="1">{{__('db.Yes')}}</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Region')}} <x-info title="Specify the region this holiday applies to" /></label>
                            <input type="text" name="region" class="form-control">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{__('db.note')}} <x-info title="Edit notes or description for this holiday" /></label>
                            <textarea name="note" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
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
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #holiday-menu").addClass("active");

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function confirmDelete() {
        return confirm("Are you sure want to delete?");
    }

    $('.date').datepicker({
        format: "dd-mm-yyyy",
        startDate: "{{ date('d-m-Y') }}",
        autoclose: true,
        todayHighlight: true
    });

    // Approve button
    $(document).on('click', '.btn-approve', function() {
        var btn = $(this);
        var id = btn.data('id');
        $.get('approve-holiday/'+id, function(data) {
            if(data.status) {
                btn.addClass('d-none');
            } else {
                alert(data.message);
            }
        });
    });

    // Edit button
    $(document).on('click', '.btn-edit', function() {
        var tr = $(this).closest('tr');
        $("input[name='id']").val(tr.data('id'));
        $("input[name='from_date']").val(tr.data('from'));
        $("input[name='to_date']").val(tr.data('to'));
        $("textarea[name='note']").val(tr.data('note'));
        $("select[name='recurring']").val(tr.data('recurring'));
        $("input[name='region']").val(tr.data('region'));
    });

    $('#holiday-table').DataTable({
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            "info": '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{__("db.Search")}}',
            'paginate': {
                'previous': '<i class="ti ti-chevron-left"></i>',
                'next': '<i class="ti ti-chevron-right"></i>'
            }
        },
        'columnDefs': [
            { "orderable": false, 'targets': [0, 8] }
        ],
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons:[
            { extend:'pdf', text:'<i class="ti ti-file-type-pdf"></i>', exportOptions:{columns:':visible:Not(.not-exported)'} },
            { extend:'excel', text:'<i class="ti ti-file-type-xls"></i>', exportOptions:{columns:':visible:Not(.not-exported)'} },
            { extend:'csv', text:'<i class="ti ti-file-type-csv"></i>', exportOptions:{columns:':visible:Not(.not-exported)'} },
            { extend:'print', text:'<i class="ti ti-printer"></i>', exportOptions:{columns:':visible:Not(.not-exported)'} },
            { extend:'colvis', text:'<i class="ti ti-eye"></i>', columns:':gt(0)'}
        ]
    });
</script>
@endpush
