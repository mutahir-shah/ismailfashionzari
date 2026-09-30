@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')

    <section>
        <div class="container-fluid"><span id="general_result"></span></div>

        <div class="container-fluid mb-3">
            @can('project_category_add')
            <button type="button" class="btn btn-info" name="create_record" id="create_record"><i class="ti ti-plus"></i> {{__('Add Project Category')}}</button>
            @endcan
        </div>

        <div class="table-responsive">
            <table id="category-table" class="table">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('Category Name')}}</th>
                    <th>{{__('db.Status')}}</th>
                    <th class="not-exported text-right">{{__('db.action')}}</th>
                </tr>
                </thead>
            </table>
        </div>
    </section>

    <div id="formModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('Add Project Category')}}</h5>
                    <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i class="ti ti-x"></i></button>
                </div>

                <div class="modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>{{__('Category Name')}} *</label>
                                <input type="text" name="name" id="name" required class="form-control" placeholder="{{__('Category Name')}}">
                            </div>

                            <div class="col-md-12 form-group">
                                <label>{{__('db.Status')}} *</label>
                                <select name="is_active" id="is_active" required class="form-control selectpicker">
                                    <option value="1">{{__('db.Active')}}</option>
                                    <option value="0">{{__('db.Inactive')}}</option>
                                </select>
                            </div>

                            <div class="form-group d-block w-100" align="center">
                                <input type="hidden" name="action" id="action"/>
                                <input type="hidden" name="hidden_id" id="hidden_id"/>
                                <input type="submit" name="action_button" id="action_button" class="btn btn-warning" value="{{__('db.add')}}" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">{{__('db.Confirmation')}}</h2>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h4 align="center">{{__('Are you sure you want to remove this data?')}}</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" name="ok_button" id="ok_button" class="btn btn-danger">{{__('db.OK')}}</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{__('db.Cancel')}}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
    <script type="text/javascript">
        (function($) {
            "use strict";

            $(document).ready(function () {
                $('#category-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('project_categories.index') }}",
                    },
                    responsive: true,
                    fixedHeader: {
                        header: true,
                        footer: true
                    },
                    columns: [
                        { data: 'id', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'is_active', name: 'is_active' },
                        { data: 'action', orderable: false, searchable: false }
                    ],
                    order: [],
                    language: {
                        lengthMenu: '_MENU_ {{ __("records per page") }}',
                        info: '{{ __("db.Showing") }} _START_ - _END_ (_TOTAL_)',
                        search: '{{ __("db.Search") }}',
                        paginate: {
                            previous: '{{ __("db.Previous") }}',
                            next: '{{ __("db.Next") }}'
                        }
                    },
                    columnDefs: [
                        {
                            targets: [0],
                            orderable: false
                        },
                        {
                            targets: [3],
                            orderable: false,
                            className: 'text-right'
                        },
                        {
                            targets: [0],
                            render: function (data, type, row) {
                                if (type === 'display') {
                                    return '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                                }
                                return data;
                            },
                            checkboxes: {
                                selectRow: true,
                                selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                            }
                        }
                    ],
                    select: {
                        style: 'multi',
                        selector: 'td:first-child'
                    },
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    dom: '<"row"lfB>rtip',
                    buttons: [
                        {
                            extend: 'pdf',
                            text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
                            exportOptions: {
                                columns: ':visible:not(.not-exported)',
                                rows: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i title="export to csv" class="ti ti-file-type-csv"></i>',
                            exportOptions: {
                                columns: ':visible:not(.not-exported)',
                                rows: ':visible'
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i title="print" class="ti ti-printer"></i>',
                            exportOptions: {
                                columns: ':visible:not(.not-exported)',
                                rows: ':visible'
                            }
                        },
                        {
                            extend: 'colvis',
                            text: '<i title="column visibility" class="ti ti-eye"></i>',
                            columns: ':gt(0)'
                        }
                    ]
                });
            });

            $('#create_record').on('click', function () {
                $('.modal-title').text("{{__('Add Project Category')}}");
                $('#action_button').val('{{__("db.add")}}');
                $('#action').val('{{__("db.add")}}');
                $('#formModal').modal('show');
            });

            $('#sample_form').on('submit', function (event) {
                event.preventDefault();
                let url = '';
                if ($('#action').val() == "{{__('db.add')}}") {
                    url = "{{ route('project_categories.store') }}";
                }
                if ($('#action').val() == "{{__('db.edit')}}") {
                    url = "{{ route('project_categories.update') }}";
                }

                $.ajax({
                    url: url,
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        var html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (var count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                            $('#sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('#category-table').DataTable().ajax.reload();
                            setTimeout(function(){
                                if (document.activeElement) { document.activeElement.blur(); }
                                $('#formModal').modal('hide');
                            }, 2000);
                        }
                        $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })
            });

            $(document).on('click', '.edit', function () {
                var id = $(this).attr('id');
                $('#form_result').html('');

                var target = "{{ url('project-management/project-categories')}}/" + id + "/edit";

                $.ajax({
                    url: target,
                    dataType: "json",
                    success: function (html) {
                        $('#name').val(html.data.name);
                        $('#is_active').selectpicker('val', html.data.is_active);
                        $('#hidden_id').val(html.data.id);
                        $('.modal-title').text("{{__('db.edit')}}");
                        $('#action_button').val("{{__('db.edit')}}");
                        $('#action').val("{{__('db.edit')}}");
                        $('#formModal').modal('show');
                    }
                })
            });

            let lid;
            $(document).on('click', '.delete', function () {
                lid = $(this).attr('id');
                $('#confirmModal').modal('show');
                $('.modal-title').text("{{__('DELETE Record')}}");
                $('#ok_button').text("{{__('db.OK')}}");
            });

            $('.close').on('click', function () {
                $('#sample_form')[0].reset();
                $('#category-table').DataTable().ajax.reload();
                $('select').selectpicker('refresh');
            });

            $('#ok_button').on('click', function () {
                var target = "{{ url('project-management/project-categories')}}/" + lid + "/delete";
                $.ajax({
                    url: target,
                    beforeSend: function () {
                        $('#ok_button').text("{{__('db.deleting')}}");
                    },
                    success: function (data) {
                        let html = '';
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        if (data.error) {
                            html = '<div class="alert alert-danger">' + data.error + '</div>';
                        }
                        setTimeout(function () {
                            $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                            $('#confirmModal').modal('hide');
                            $('#category-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                })
            });

        })(jQuery);
    </script>
@endpush
