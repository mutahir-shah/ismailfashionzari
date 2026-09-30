@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
<style>
    .dataTable {
        width: 100% !important;
    }
</style>

<section>

    <div class="container-fluid"><span id="general_result"></span></div>


    <div class="container-fluid mb-3">
        @can('project_project_add')
        <button type="button" class="btn btn-info" name="create_record" id="create_record"><i
                class="ti ti-plus"></i> {{__('Add Project')}}</button>
        @endcan
    </div>

    <div class="table-responsive">
        <table id="project-table" class="table ">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('Project Name')}}</th>
                    <th>{{__('db.priority')}}</th>
                    <th>{{__('Assigned Employees')}}</th>
                    <th>{{__('db.customer')}}</th>
                    <th>{{__('Start Date')}}</th>
                    <th>{{__('End Date')}}</th>
                    <th>{{__('db.Status')}}</th>
                    <th class="not-exported">{{__('db.action')}}</th>
                </tr>
            </thead>

        </table>
    </div>
</section>



<div id="formModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('Add Project')}}</h5>
                <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i
                        class="ti ti-x"></i></button>
            </div>

            <div id="form_result"></div>
            <form method="post" id="sample_form" class="form-horizontal">

                @csrf
                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 form-group">
                            <label>{{__('db.Title')}} *</label>
                            <input type="text" name="title" id="title" required class="form-control"
                                placeholder="{{__('db.Title')}}">
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('Project Category')}}</label>
                                <select name="project_category_id" id="project_category_id"
                                    class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title='{{__('Selecting',['key'=>__('Project Category')])}}...'>
                                    @foreach($categories as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('db.customer')}}*</label>
                                <select name="customer_id" id="customer_id"
                                    class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title='{{__('Selecting',['key'=>trans('db.customer')])}}...'>
                                    @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="col-md-6 form-group">
                            <label>{{__('Start Date')}} *</label>
                            <input type="text" name="start_date" id="start_date" autocomplete="off" required
                                class="form-control date"
                                value="">
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{__('End Date')}} *</label>
                            <input type="text" name="end_date" id="end_date" autocomplete="off" required
                                class="form-control date"
                                value="">
                        </div>

                        <div class="col-md-4 form-group">
                            <label>{{__('db.priority')}}</label>
                            <select name="project_priority" id="project_priority" class="form-control selectpicker "
                                data-live-search="true" data-live-search-style="contains"
                                title='{{__('Selecting',['key'=>'Priority'])}}...'>
                                <option value="low">{{__('Low')}}</option>
                                <option value="medium">{{__('Medium')}}</option>
                                <option value="high">{{__('High')}}</option>
                                <option value="highest">{{__('Highest')}}</option>
                            </select>
                        </div>


                        <div class="col-md-4 form-group">
                            <label>{{__('Assigned Employees')}} * @can('project_employee_add') <a href="{{route('employees.create')}}" class="btn btn-link btn-sm p-0"><i class="ti ti-plus"></i></a> @endif</label>
                            <select name="employee_id[]" id="employee_id" class="form-control selectpicker" data-live-search="true" data-live-search-style="contains"
                                multiple="multiple">
                                @foreach($employees as $employee)
                                <option value="{{$employee->id}}">{{$employee->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label>{{__('Status')}} *</label>
                            <select name="project_status" class="form-control selectpicker" required>
                                <option value="not_started" selected>{{__('Not Started')}}</option>
                                <option value="in_progress">{{__('In Progress')}}</option>
                                <option value="completed">{{__('Completed')}}</option>
                                <option value="deferred">{{__('Deferred')}}</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{__('db.Summary')}}</label>
                                <textarea class="form-control" id="summary"
                                    name="summary" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{__('db.Description')}}</label>
                                <textarea class="form-control des-editor" id="description" name="description"
                                    rows="3"></textarea>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="is_notify_employee" id="is_notify_employee" value="1">
                                <label class="custom-control-label" for="is_notify_employee">{{__('Notify Assigned Employees (WhatsApp)')}}</label>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="is_notify_customer" id="is_notify_customer" value="1">
                                <label class="custom-control-label" for="is_notify_customer">{{__('Notify Customer (WhatsApp)')}}</label>
                            </div>
                        </div>
                    </div>


                </div>
                <input type="submit" name="action_button" id="action_button"
                    class="btn btn-warning btn-block btn-lg" value="{{__('db.add')}}">
            </form>
        </div>
    </div>
</div>


<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('Edit Project')}}</h5>
                <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i
                        class="ti ti-x"></i></button>
            </div>

            <span id="edit_form_result"></span>
            <form method="post" id="edit_sample_form" class="form-horizontal">

                @csrf

                <div class="modal-body">
                    <div class="row">

                        <div class="col-md-6 form-group">
                            <label>{{__('db.Title')}} *</label>
                            <input type="text" name="edit_title" id="edit_title" required
                                class="form-control"
                                placeholder="{{__('db.Title')}}">
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('Project Category')}}</label>
                                <select name="edit_project_category_id" id="edit_project_category_id"
                                    class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title='{{__('Selecting',['key'=>__('Project Category')])}}...'>
                                    @foreach($categories as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('db.customer')}}*</label>
                                <select name="edit_customer_id" id="edit_customer_id"
                                    class="form-control selectpicker"
                                    data-live-search="true" data-live-search-style="contains"
                                    title='{{__('Selecting',['key'=>trans('db.customer')])}}...'>
                                    @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="col-md-6 form-group">
                            <label>{{__('Start Date')}} *</label>
                            <input type="text" name="edit_start_date" id="edit_start_date" autocomplete="off"
                                required class="form-control date"
                                value="">
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{__('Assigned Employees')}} *</label>
                            <select name="edit_employee_id[]" id="edit_employee_id" class="form-control selectpicker w-100" multiple="multiple">
                                @foreach($employees as $employee)
                                <option value="{{$employee->id}}">{{$employee->name}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{__('End Date')}} *</label>
                            <input type="text" name="edit_end_date" id="edit_end_date" autocomplete="off" required
                                class="form-control date"
                                value="">
                        </div>


                        <div class="col-md-6 form-group">
                            <label>{{__('db.priority')}}</label>
                            <select name="edit_project_priority" id="edit_project_priority"
                                class="form-control selectpicker "
                                data-live-search="true" data-live-search-style="contains"
                                title='{{__('Selecting',['key'=>__('db.priority')])}}...'>
                                <option value="low">{{__('Low')}}</option>
                                <option value="medium">{{__('Medium')}}</option>
                                <option value="high">{{__('High')}}</option>
                                <option value="highest">{{__('Highest')}}</option>
                            </select>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{__('db.Status')}} *</label>
                            <select name="edit_project_status" id="edit_project_status"
                                class="form-control selectpicker " required
                                data-live-search="true" data-live-search-style="contains"
                                title='{{__('Selecting',['key'=>trans('db.Status')])}}...'>
                                <option value="not_started">{{__('Not Started')}}</option>
                                <option value="in_progress">{{__('In Progress')}}</option>
                                <option value="completed">{{__('db.Completed')}}</option>
                                <option value="deferred">{{__('db.deferred')}}</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{__('db.Summary')}}</label>
                                <textarea class="form-control" id="edit_summary"
                                    name="edit_summary" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{__('db.Description')}}</label>
                                <textarea class="form-control des-editor" id="edit_description"
                                    name="edit_description"
                                    rows="3"></textarea>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="edit_is_notify_employee" id="edit_is_notify_employee" value="1">
                                <label class="custom-control-label" for="edit_is_notify_employee">{{__('Notify Assigned Employees (WhatsApp)')}}</label>
                            </div>
                        </div>

                        <div class="col-md-6 form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="edit_is_notify_customer" id="edit_is_notify_customer" value="1">
                                <label class="custom-control-label" for="edit_is_notify_customer">{{__('Notify Customer (WhatsApp)')}}</label>
                            </div>
                        </div>

                    </div>

                </div>
                <input type="hidden" name="hidden_id" id="hidden_id" />
                <input type="submit" name="edit_action_button" id="edit_action_button"
                    class="btn btn-warning btn-block btn-lg" value={{__("db.edit")}}>
            </form>
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
                <button type="button" name="ok_button" id="ok_button" class="btn btn-danger">{{__('db.delete')}}'
                </button>
                <button type="button" class="close btn-default"
                    data-dismiss="modal">{{__('db.Cancel')}}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script type="text/javascript">
    (function($) {

        $(document).ready(function() {

            let date = $('.date');
            date.datepicker({
                format: "{{ env('Date_Format_JS')}}",
                autoclose: true,
                todayHighlight: true
            });


            var table_table = $('#project-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('projects.index') }}",
                },
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'summary',
                        name: 'summary'
                    },
                    {
                        data: 'project_priority',
                        name: 'project_priority'
                    },
                    {
                        data: 'assigned_employee',
                        name: 'assigned_employee',
                        render: function(data) {
                            return Array.isArray(data) ? data.join("<br>") : data;
                        }
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date'
                    },
                    {
                        data: 'project_status',
                        name: 'project_status'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                language: {
                    lengthMenu: '_MENU_ {{__("records per page")}}',
                    info: '{{ __("Showing") }} _START_ - _END_ (_TOTAL_)',
                    search: '{{ __("Search") }}',
                    paginate: {
                        previous: '{{ __("Previous") }}',
                        next: '{{ __("Next") }}'
                    }
                },
                columnDefs: [{
                        targets: [0, 7],
                        orderable: false
                    },
                    {
                        targets: [0],
                        render: function(data, type, row, meta) {
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
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                dom: '<"row"lfB>rtip',
                buttons: [{
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
                ],
                initComplete: function() {
                    // Per-column select filter on column 1 (summary)
                    this.api().columns([1]).every(function() {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function() {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function(d) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                        });

                        $('select').selectpicker('refresh'); // ensure this is only called after building the select
                    });
                }
            });

            new $.fn.dataTable.FixedHeader(table_table);

        });

        $('#create_record').on('click', function() {

            $('#formModal').modal('show');
        });

        $('#sample_form').on('submit', function(event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('projects.store') }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function(data) {
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
                        $('.js-example-responsive').val(null).trigger('change');
                        $('#project-table').DataTable().ajax.reload();
                        setTimeout(function() {
                            $('#formModal').modal('hide');
                        }, 2000);
                    }
                    $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $('#edit_sample_form').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('projects.update') }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function(data) {
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
                        setTimeout(function() {
                            $('#editModal').modal('hide');
                            $('select').selectpicker('refresh');
                            $('.js-example-responsive').val(null).trigger('change');
                            $('#project-table').DataTable().ajax.reload();
                            $('#edit_sample_form')[0].reset();
                        }, 2000);

                    }
                    $('#edit_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            });
        });


        $(document).on('click', '.edit', function() {

            var id = $(this).attr('id');
            $('#edit_form_result').html('');


            var target = "{{ route('projects.index') }}/" + id + '/edit';

            $.ajax({
                url: target,
                dataType: "json",
                success: function(html) {

                    $('#edit_title').val(html.data.title);
                    $('#edit_project_priority').selectpicker('val', html.data.project_priority);
                    $('#edit_customer_id').selectpicker('val', html.data.customer_id);

                    $('#edit_start_date').val(html.data.start_date);
                    $('#edit_end_date').val(html.data.end_date);
                    $('#edit_project_category_id').selectpicker('val', html.data.project_category_id);
                    $('#edit_employee_id').selectpicker('val', html.employee_ids);
                    if (html.data.project_status) {
                        let normalizedStatus = html.data.project_status.replace(' ', '_');
                        $('#edit_project_status').selectpicker('val', normalizedStatus);
                    }
                    $('#edit_summary').val(html.data.summary);

                    $('#hidden_id').val(html.data.id);
                    $('#editModal').modal('show');

                    if (html.data.description) {
                        function htmlDecode(input) {
                            var e = document.createElement('div');
                            e.innerHTML = input;
                            return e.childNodes.length == 0 ? "" : e.childNodes[0].nodeValue;
                        }
                        // tinymce.get('edit_description').setContent(htmlDecode(html.data.description));
                        $('#edit_description').html(html.data.description);
                    }

                    tinymce.init({
                        selector: '.des-editor',
                        setup: function(editor) {
                            editor.on('change', function() {
                                editor.save();
                            });
                        },
                        height: 130,

                        image_title: true,
                        /* enable automatic uploads of images represented by blob or data URIs*/
                        automatic_uploads: true,
                        /*
                        URL of our upload handler (for more details check: https://www.tiny.cloud/docs/configure/file-image-upload/#images_upload_url)
                        images_upload_url: 'postAcceptor.php',
                        here we add custom filepicker only to Image dialog
                        */
                        file_picker_types: 'image',
                        /* and here's our custom image picker*/
                        file_picker_callback: function(cb, value, meta) {
                            var input = document.createElement('input');
                            input.setAttribute('type', 'file');
                            input.setAttribute('accept', 'image/*');

                            /*
                            Note: In modern browsers input[type="file"] is functional without
                            even adding it to the DOM, but that might not be the case in some older
                            or quirky browsers like IE, so you might want to add it to the DOM
                            just in case, and visually hide it. And do not forget do remove it
                            once you do not need it anymore.
                            */

                            input.onchange = function() {
                                var file = this.files[0];

                                var reader = new FileReader();
                                reader.onload = function() {
                                    /*
                                    Note: Now we need to register the blob in TinyMCEs image blob
                                    registry. In the next release this part hopefully won't be
                                    necessary, as we are looking to handle it internally.
                                    */
                                    var id = 'blobid' + (new Date()).getTime();
                                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                    var base64 = reader.result.split(',')[1];
                                    var blobInfo = blobCache.create(id, file, base64);
                                    blobCache.add(blobInfo);

                                    /* call the callback and populate the Title field with the file name */
                                    cb(blobInfo.blobUri(), {
                                        title: db.name
                                    });
                                };
                                reader.readAsDataURL(file);
                            };

                            input.click();
                        },

                        plugins: [
                            'advlist autolink lists link image charmap print preview anchor textcolor',
                            'searchreplace visualblocks code fullscreen',
                            'insertdatetime media table contextmenu paste code wordcount'
                        ],
                        toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
                        branding: false
                    });
                }
            })
        });


        let delete_id;

        $(document).on('click', '.delete', function() {
            delete_id = $(this).attr('id');
            $('#confirmModal').modal('show');
            $('.modal-title').text("{{__('DELETE Record')}}");
            $('#ok_button').text("{{__('db.delete')}}");

        });


        $('.close').on('click', function() {
            $('#sample_form')[0].reset();
            $('#edit_sample_form')[0].reset();
            $('select').selectpicker('refresh');
            $('#project-table').DataTable().ajax.reload();
        });

        $('#ok_button').on('click', function() {
            let target = "{{ route('projects.index') }}/" + delete_id + '/delete';
            $.ajax({
                url: target,
                beforeSend: function() {
                    $('#ok_button').text("{{__('Deleting...')}}");
                },
                success: function(data) {
                    let html = '';
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    if (data.error) {
                        html = '<div class="alert alert-danger">' + data.error + '</div>';
                    }
                    setTimeout(function() {
                        $('#general_result').html(html).slideDown(300).delay(5000).slideUp(300);
                        $('#confirmModal').modal('hide');
                        $('#project-table').DataTable().ajax.reload();
                    }, 2000);
                }
            })
        });



    })(jQuery);
</script>
@endpush