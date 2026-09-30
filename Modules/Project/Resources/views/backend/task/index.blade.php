@extends('backend.layout.main')
@push('css')
    @include('backend.layout.partials.datatable_css')
@endpush

@section('content')
    <style>
        /*!Ion.RangeSlider, 2.3.1, © Denis Ineshin, 2010 - 2019, IonDen.com, Build date: 2019-12-19 16:51:02*/.irs{position:relative;display:block;-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;font-size:12px;font-family:Arial,sans-serif}.irs-line{position:relative;display:block;overflow:hidden;outline:none !important}.irs-bar{position:absolute;display:block;left:0;width:0}.irs-shadow{position:absolute;display:none;left:0;width:0}.irs-handle{position:absolute;display:block;box-sizing:border-box;cursor:default;z-index:1}.irs-handle.type_last{z-index:2}.irs-min,.irs-max{position:absolute;display:block;cursor:default}.irs-min{left:0}.irs-max{right:0}.irs-from,.irs-to,.irs-single{position:absolute;display:block;top:0;left:0;cursor:default;white-space:nowrap}.irs-grid{position:absolute;display:none;bottom:0;left:0;width:100%;height:20px}.irs-with-grid .irs-grid{display:block}.irs-grid-pol{position:absolute;top:0;left:0;width:1px;height:8px;background:#000}.irs-grid-pol.small{height:4px}.irs-grid-text{position:absolute;bottom:0;left:0;white-space:nowrap;text-align:center;font-size:9px;line-height:9px;padding:0 3px;color:#000}.irs-disable-mask{position:absolute;display:block;top:0;left:-1%;width:102%;height:100%;cursor:default;background:rgba(0,0,0,0);z-index:2}.lt-ie9 .irs-disable-mask{background:#000;filter:alpha(opacity=0);cursor:not-allowed}.irs-disabled{opacity:.4}.irs-hidden-input{position:absolute !important;display:block !important;top:0 !important;left:0 !important;width:0 !important;height:0 !important;font-size:0 !important;line-height:0 !important;padding:0 !important;margin:0 !important;overflow:hidden;outline:none !important;z-index:-9999 !important;background:none !important;border-style:solid !important;border-color:transparent !important}.irs--flat{height:40px}.irs--flat.irs-with-grid{height:60px}.irs--flat .irs-line{top:25px;height:12px;background-color:#e1e4e9;border-radius:4px}.irs--flat .irs-bar{top:25px;height:12px;background-color:#ed5565}.irs--flat .irs-bar--single{border-radius:4px 0 0 4px}.irs--flat .irs-shadow{height:1px;bottom:16px;background-color:#e1e4e9}.irs--flat .irs-handle{top:22px;width:16px;height:18px;background-color:transparent}.irs--flat .irs-handle>i:first-child{position:absolute;display:block;top:0;left:50%;width:2px;height:100%;margin-left:-1px;background-color:#da4453}.irs--flat .irs-handle.state_hover>i:first-child,.irs--flat .irs-handle:hover>i:first-child{background-color:#a43540}.irs--flat .irs-min,.irs--flat .irs-max{top:0;padding:1px 3px;color:#999;font-size:10px;line-height:1.333;text-shadow:none;background-color:#e1e4e9;border-radius:4px}.irs--flat .irs-from,.irs--flat .irs-to,.irs--flat .irs-single{color:white;font-size:10px;line-height:1.333;text-shadow:none;padding:1px 5px;background-color:#ed5565;border-radius:4px}.irs--flat .irs-from:before,.irs--flat .irs-to:before,.irs--flat .irs-single:before{position:absolute;display:block;content:"";bottom:-6px;left:50%;width:0;height:0;margin-left:-3px;overflow:hidden;border:3px solid transparent;border-top-color:#ed5565}.irs--flat .irs-grid-pol{background-color:#e1e4e9}.irs--flat .irs-grid-text{color:#999}.irs--big{height:55px}.irs--big.irs-with-grid{height:70px}.irs--big .irs-line{top:33px;height:12px;background-color:white;background:linear-gradient(to bottom, #ddd -50%, white 150%);border:1px solid #ccc;border-radius:12px}.irs--big .irs-bar{top:33px;height:12px;background-color:#92bce0;border:1px solid #428bca;background:linear-gradient(to bottom, #ffffff 0%, #428bca 30%, #b9d4ec 100%);box-shadow:inset 0 0 1px 1px rgba(255,255,255,0.5)}.irs--big .irs-bar--single{border-radius:12px 0 0 12px}.irs--big .irs-shadow{height:1px;bottom:16px;background-color:rgba(66,139,202,0.5)}.irs--big .irs-handle{top:25px;width:30px;height:30px;border:1px solid rgba(0,0,0,0.3);background-color:#cbcfd5;background:linear-gradient(to bottom, white 0%, #B4B9BE 30%, white 100%);box-shadow:1px 1px 2px rgba(0,0,0,0.2),inset 0 0 3px 1px white;border-radius:30px}.irs--big .irs-handle.state_hover,.irs--big .irs-handle:hover{border-color:rgba(0,0,0,0.45);background-color:#939ba7;background:linear-gradient(to bottom, white 0%, #919BA5 30%, white 100%)}.irs--big .irs-min,.irs--big .irs-max{top:0;padding:1px 5px;color:white;text-shadow:none;background-color:#9f9f9f;border-radius:3px}.irs--big .irs-from,.irs--big .irs-to,.irs--big .irs-single{color:white;text-shadow:none;padding:1px 5px;background-color:#428bca;background:linear-gradient(to bottom, #428bca 0%, #3071a9 100%);border-radius:3px}.irs--big .irs-grid-pol{background-color:#428bca}.irs--big .irs-grid-text{color:#428bca}.irs--modern{height:55px}.irs--modern.irs-with-grid{height:55px}.irs--modern .irs-line{top:25px;height:5px;background-color:#d1d6e0;background:linear-gradient(to bottom, #e0e4ea 0%, #d1d6e0 100%);border:1px solid #a3adc1;border-bottom-width:0;border-radius:5px}.irs--modern .irs-bar{top:25px;height:5px;background:#20b426;background:linear-gradient(to bottom, #20b426 0%, #18891d 100%)}.irs--modern .irs-bar--single{border-radius:5px 0 0 5px}.irs--modern .irs-shadow{height:1px;bottom:21px;background-color:rgba(209,214,224,0.5)}.irs--modern .irs-handle{top:37px;width:12px;height:13px;border:1px solid #a3adc1;border-top-width:0;box-shadow:1px 1px 1px rgba(0,0,0,0.1);border-radius:0 0 3px 3px}.irs--modern .irs-handle>i:nth-child(1){position:absolute;display:block;top:-4px;left:1px;width:6px;height:6px;border:1px solid #a3adc1;background:white;transform:rotate(45deg)}.irs--modern .irs-handle>i:nth-child(2){position:absolute;display:block;box-sizing:border-box;top:0;left:0;width:10px;height:12px;background:#e9e6e6;background:linear-gradient(to bottom, white 0%, #e9e6e6 100%);border-radius:0 0 3px 3px}.irs--modern .irs-handle>i:nth-child(3){position:absolute;display:block;box-sizing:border-box;top:3px;left:3px;width:4px;height:5px;border-left:1px solid #a3adc1;border-right:1px solid #a3adc1}.irs--modern .irs-handle.state_hover,.irs--modern .irs-handle:hover{border-color:#7685a2;background:#c3c7cd;background:linear-gradient(to bottom, #ffffff 0%, #919ba5 30%, #ffffff 100%)}.irs--modern .irs-handle.state_hover>i:nth-child(1),.irs--modern .irs-handle:hover>i:nth-child(1){border-color:#7685a2}.irs--modern .irs-handle.state_hover>i:nth-child(3),.irs--modern .irs-handle:hover>i:nth-child(3){border-color:#48536a}.irs--modern .irs-min,.irs--modern .irs-max{top:0;font-size:10px;line-height:1.333;text-shadow:none;padding:1px 5px;color:white;background-color:#d1d6e0;border-radius:5px}.irs--modern .irs-from,.irs--modern .irs-to,.irs--modern .irs-single{font-size:10px;line-height:1.333;text-shadow:none;padding:1px 5px;background-color:#20b426;color:white;border-radius:5px}.irs--modern .irs-from:before,.irs--modern .irs-to:before,.irs--modern .irs-single:before{position:absolute;display:block;content:"";bottom:-6px;left:50%;width:0;height:0;margin-left:-3px;overflow:hidden;border:3px solid transparent;border-top-color:#20b426}.irs--modern .irs-grid{height:25px}.irs--modern .irs-grid-pol{background-color:#dedede}.irs--modern .irs-grid-text{color:silver;font-size:13px}.irs--sharp{height:50px;font-size:12px;line-height:1}.irs--sharp.irs-with-grid{height:57px}.irs--sharp .irs-line{top:30px;height:2px;background-color:black;border-radius:2px}.irs--sharp .irs-bar{top:30px;height:2px;background-color:#ee22fa}.irs--sharp .irs-bar--single{border-radius:2px 0 0 2px}.irs--sharp .irs-shadow{height:1px;bottom:21px;background-color:rgba(0,0,0,0.5)}.irs--sharp .irs-handle{top:25px;width:10px;height:10px;background-color:#a804b2}.irs--sharp .irs-handle>i:first-child{position:absolute;display:block;top:100%;left:0;width:0;height:0;border:5px solid transparent;border-top-color:#a804b2}.irs--sharp .irs-handle.state_hover,.irs--sharp .irs-handle:hover{background-color:black}.irs--sharp .irs-handle.state_hover>i:first-child,.irs--sharp .irs-handle:hover>i:first-child{border-top-color:black}.irs--sharp .irs-min,.irs--sharp .irs-max{color:white;font-size:14px;line-height:1;top:0;padding:3px 4px;opacity:.4;background-color:#a804b2;border-radius:2px}.irs--sharp .irs-from,.irs--sharp .irs-to,.irs--sharp .irs-single{font-size:14px;line-height:1;text-shadow:none;padding:3px 4px;background-color:#a804b2;color:white;border-radius:2px}.irs--sharp .irs-from:before,.irs--sharp .irs-to:before,.irs--sharp .irs-single:before{position:absolute;display:block;content:"";bottom:-6px;left:50%;width:0;height:0;margin-left:-3px;overflow:hidden;border:3px solid transparent;border-top-color:#a804b2}.irs--sharp .irs-grid{height:25px}.irs--sharp .irs-grid-pol{background-color:#dedede}.irs--sharp .irs-grid-text{color:silver;font-size:13px}.irs--round{height:50px}.irs--round.irs-with-grid{height:65px}.irs--round .irs-line{top:36px;height:4px;background-color:#dee4ec;border-radius:4px}.irs--round .irs-bar{top:36px;height:4px;background-color:#006cfa}.irs--round .irs-bar--single{border-radius:4px 0 0 4px}.irs--round .irs-shadow{height:4px;bottom:21px;background-color:rgba(222,228,236,0.5)}.irs--round .irs-handle{top:26px;width:24px;height:24px;border:4px solid #006cfa;background-color:white;border-radius:24px;box-shadow:0 1px 3px rgba(0,0,255,0.3)}.irs--round .irs-handle.state_hover,.irs--round .irs-handle:hover{background-color:#f0f6ff}.irs--round .irs-min,.irs--round .irs-max{color:#333;font-size:14px;line-height:1;top:0;padding:3px 5px;background-color:rgba(0,0,0,0.1);border-radius:4px}.irs--round .irs-from,.irs--round .irs-to,.irs--round .irs-single{font-size:14px;line-height:1;text-shadow:none;padding:3px 5px;background-color:#006cfa;color:white;border-radius:4px}.irs--round .irs-from:before,.irs--round .irs-to:before,.irs--round .irs-single:before{position:absolute;display:block;content:"";bottom:-6px;left:50%;width:0;height:0;margin-left:-3px;overflow:hidden;border:3px solid transparent;border-top-color:#006cfa}.irs--round .irs-grid{height:25px}.irs--round .irs-grid-pol{background-color:#dedede}.irs--round .irs-grid-text{color:silver;font-size:13px}.irs--square{height:50px}.irs--square.irs-with-grid{height:60px}.irs--square .irs-line{top:31px;height:4px;background-color:#dedede}.irs--square .irs-bar{top:31px;height:4px;background-color:black}.irs--square .irs-shadow{height:2px;bottom:21px;background-color:#dedede}.irs--square .irs-handle{top:25px;width:16px;height:16px;border:3px solid black;background-color:white;-webkit-transform:rotate(45deg);-ms-transform:rotate(45deg);transform:rotate(45deg)}.irs--square .irs-handle.state_hover,.irs--square .irs-handle:hover{background-color:#f0f6ff}.irs--square .irs-min,.irs--square .irs-max{color:#333;font-size:14px;line-height:1;top:0;padding:3px 5px;background-color:rgba(0,0,0,0.1)}.irs--square .irs-from,.irs--square .irs-to,.irs--square .irs-single{font-size:14px;line-height:1;text-shadow:none;padding:3px 5px;background-color:black;color:white}.irs--square .irs-grid{height:25px}.irs--square .irs-grid-pol{background-color:#dedede}.irs--square .irs-grid-text{color:silver;font-size:11px}
        .dataTable{width: 100% !important;}
    </style>

    <section>

        <div class="container-fluid"><span id="general_result"></span></div>


        <div class="container-fluid mb-3">
                @can('project_task_add')
                <button type="button" class="btn btn-info" name="create_record" id="create_record"><i
                            class="ti ti-plus"></i> {{__('db.Add Task')}}</button>
                @endcan
        </div>

        <div class="table-responsive">
            <table id="task-table" class="table ">
                <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{__('db.Title')}}</th>
                    <th>{{__('db.Start Date')}}</th>
                    <th>{{__('db.End Date')}}</th>
                    <th>{{__('db.Status')}}</th>
                    <th>{{__('Assigned Employees')}}</th>
                    <th>{{__('db.Created By')}}</th>

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
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Task')}}</h5>
                    <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i class="ti ti-x"></i></button>
                </div>

                <div class="modal-body">
                    <span id="form_result"></span>
                    <form method="post" id="sample_form" class="form-horizontal">

                        @csrf

                        <div class="row">

                            <div class="col-md-6 form-group">
                                <label>{{__('db.Title')}} *</label>
                                <input type="text" name="task_name" id="task_name" required class="form-control"
                                       placeholder="{{__('db.Title')}}">
                            </div>



                            <div class="col-md-6 form-group">
                                <label>{{__('db.Start Date')}} *</label>
                                <input type="text" name="start_date" id="start_date" autocomplete="off" required
                                       class="form-control date"
                                       value="">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('db.End Date')}} *</label>
                                <input type="text" name="end_date" id="end_date" autocomplete="off" required
                                       class="form-control date"
                                       value="">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('db.project')}}</label>
                                <select name="project_id" id="project_id" class="form-control selectpicker "
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{__('db.Selecting',['key'=>__('db.project')])}}...'>
                                    @foreach($projects as $project)
                                        <option value="{{$project->id}}">{{$project->title}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('Estimated Hour')}} *</label>
                                <input type="text" name="task_hour" id="task_hour" required class="form-control"
                                       placeholder="{{__('Estimated Hour')}}">
                            </div>

                            <div class="col-md-4 form-group">
                                <label>{{__('Project Users')}} * @can('project_employee_add') <a href="{{route('employees.create')}}" class="btn btn-link btn-sm p-0"><i class="ti ti-plus"></i></a> @endif</label>
                                <select name="employee_id[]" id="employee_id" class="form-control selectpicker w-100" multiple="multiple">
                                    @foreach($employees as $employee)
                                        <option value="{{$employee->id}}">{{$employee->name}}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Description')}}</label>
                                    <textarea class="form-control des-editor" id="description" name="description"
                                              rows="3"></textarea>
                                </div>
                            </div>

                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="is_notify" id="is_notify" value="1">
                                    <label class="custom-control-label" for="is_notify">{{__('Send WhatsApp Notification')}}</label>
                                </div>
                            </div>

                            <div class="container">
                                <div class="form-group" align="center">
                                    <input type="submit" name="action_button" id="action_button" class="btn btn-warning"
                                           value={{__('db.add')}}>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Edit Task')}}</h5>
                    <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><i class="ti ti-x"></i></button>
                </div>

                <div class="modal-body">
                    <span id="edit_form_result"></span>
                    <form method="post" id="edit_sample_form" class="form-horizontal">

                        @csrf

                        <div class="row">

                            <div class="col-md-6 form-group">
                                <label>{{__('db.Title')}} *</label>
                                <input type="text" name="edit_task_name" id="edit_task_name" required
                                       class="form-control"
                                       placeholder="{{__('db.Title')}}">
                            </div>



                            <div class="col-md-6 form-group">
                                <label>{{__('db.Start Date')}} *</label>
                                <input type="text" name="edit_start_date" id="edit_start_date" autocomplete="off"
                                       required class="form-control date"
                                       value="">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('db.End Date')}} *</label>
                                <input type="text" name="edit_end_date" id="edit_end_date" autocomplete="off" required
                                       class="form-control date"
                                       value="">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('db.project')}}</label>
                                <select name="edit_project_id" id="edit_project_id" class="form-control selectpicker "
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{__('db.Selecting',['key'=>__('db.project')])}}...'>
                                    @foreach($projects as $project)
                                        <option value="{{$project->id}}">{{$project->title}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('Estimated Hour')}} *</label>
                                <input type="text" name="edit_task_hour" id="edit_task_hour" required
                                       class="form-control"
                                       placeholder="{{__('Estimated Hour')}}">
                            </div>

                            <div class="col-md-6 form-group">
                                <label>{{__('db.Status')}}</label>
                                <select name="edit_task_status" id="edit_task_status" class="form-control selectpicker "
                                        data-live-search="true" data-live-search-style="contains"
                                        title='{{__('db.Selecting',['key'=>__('db.Status')])}}...'>
                                    <option value="not started">Not Started</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">{{__('db.Completed')}}</option>
                                </select>
                            </div>

                            <div class="col-md-12 form-group">
                                <label>{{__('Project Users')}} *</label>
                                <select name="edit_employee_id[]" id="edit_employee_id" class="form-control selectpicker w-100" multiple="multiple">
                                    @foreach($employees as $employee)
                                        <option value="{{$employee->id}}">{{$employee->name}}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{__('db.Description')}}</label>
                                    <textarea class="form-control des-editor" id="edit_description"
                                              name="edit_description"
                                              rows="3"></textarea>
                                </div>
                            </div>

                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="edit_is_notify" id="edit_is_notify" value="1">
                                    <label class="custom-control-label" for="edit_is_notify">{{__('Send WhatsApp Notification')}}</label>
                                </div>
                            </div>

                            <div class="container">
                                <div class="form-group" align="center">
                                    <input type="hidden" name="hidden_id" id="hidden_id"/>
                                    <input type="submit" name="edit_action_button" id="edit_action_button"
                                           class="btn btn-warning"
                                           value="{{__('db.edit')}}">
                                </div>
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
                    <button type="button" name="ok_button" id="ok_button" class="btn btn-danger">{{__('db.OK')}}'
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
<script>
{!! file_get_contents(Module::find('Project')->getPath(). "/assets/js/ion.rangeSlider.min.js") !!}
</script>
<script type="text/javascript">
    (function($) {
        "use strict";

        $(document).ready(function () {

            let date = $('.date');
            date.datepicker({
                format: "{{ env('Date_Format_JS')}}",
                autoclose: true,
                todayHighlight: true
            });

            var table_table = $('#task-table').DataTable({
                initComplete: function () {
                    this.api().columns([1]).every(function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );

                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                            $('select').selectpicker('refresh');
                        });
                    });
                },
                responsive: true,
                fixedHeader: {
                    header: true,
                    footer: true
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('tasks.index') }}",
                },

                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'task_name',
                        name: 'task_name',
                    },

                    {
                        data: 'start_date',
                        name: 'start_date',
                    },

                    {
                        data: 'end_date',
                        name: 'end_date',
                    },
                    {
                        data: 'task_status',
                        name: 'task_status',
                    },
                    {
                        data: 'assigned_employee',
                        name: 'assigned_employee',
                        render: function (data) {
                            return Array.isArray(data) ? data.join("<br>") : data;
                        }
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false
                    }
                ],


                "order": [],
                'language': {
                    'lengthMenu': '_MENU_ {{__("records per page")}}',
                    "info": '{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)',
                    "search": '{{__("db.Search")}}',
                    'paginate': {
                        'previous': '{{__("db.Previous")}}',
                        'next': '{{__("db.Next")}}'
                    }
                },
                'columnDefs': [
                    {
                        "orderable": false,
                        'targets': [0],
                    },
                    {
                        'render': function (data, type, row, meta) {
                            if (type == 'display') {
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


                'select': {style: 'multi', selector: 'td:first-child'},
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: '<"row"lfB>rtip',
                buttons: [
                    {
                        extend: 'pdf',
                        text: '<i title="export to pdf" class="ti ti-file-type-pdf"></i>',
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
                            rows: ':visible'
                        },
                    },
                    {
                        extend: 'print',
                        text: '<i title="print" class="ti ti-printer"></i>',
                        exportOptions: {
                            columns: ':visible:Not(.not-exported)',
                            rows: ':visible'
                        },
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="ti ti-eye"></i>',
                        columns: ':gt(0)'
                    },
                ],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        });

        $(".range-slider").ionRangeSlider({
            type: "single",
            min: 0,
            max: 100,
            step: 1,
            grid: true,
            postfix: "%",
            skin: "round"
        });


        tinymce.init({
            selector: '.des-editor',
            setup: function (editor) {
                editor.on('change', function () {
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
            file_picker_callback: function (cb, value, meta) {
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

                input.onchange = function () {
                    var file = this.files[0];

                    var reader = new FileReader();
                    reader.onload = function () {
                        /*
                          Note: Now we need to register the blob in TinyMCEs image blob
                          registry. In the next release this part hopefully won't be
                          necessary, as we are looking to handle it internally.
                        */
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);

                        /* call the callback and populate the Title field with the file name */
                        cb(blobInfo.blobUri(), { title: file.name });
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


        $('#create_record').on('click', function () {

            $('#formModal').modal('show');
        });

        $('#sample_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('tasks.store') }}",
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
                        setTimeout(function () {
                            $('#formModal').modal('hide');
                            $('#sample_form')[0].reset();
                            $('select').selectpicker('refresh');
                            $('.js-example-responsive').val(null).trigger('change');
                            $('#task-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                    $('#form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            });
        });

        $('#edit_sample_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('tasks.update') }}",
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
                        setTimeout(function () {
                            $('#editModal').modal('hide');
                            $('select').selectpicker('refresh');
                            $('#task-table').DataTable().ajax.reload();
                            $('#edit_sample_form')[0].reset();
                        }, 2000);

                    }
                    $('#edit_form_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            });
        });


        $(document).on('click', '.edit', function () {

            var id = $(this).attr('id');
            $('#edit_form_result').html('');


            var target = "{{ route('tasks.index') }}/" + id + '/edit';

            $.ajax({
                url: target,
                dataType: "json",
                success: function (html) {

                    $('#edit_task_name').val(html.data.task_name);
                    $('#edit_project_id').selectpicker('val', html.data.project_id);
                    $('#edit_employee_id').selectpicker('val', html.employee_ids);
                    $('#edit_task_status').selectpicker('val', html.data.task_status);
                    if (html.data.description) {
                        function htmlDecode(input){
                            var e = document.createElement('div');
                            e.innerHTML = input;
                            return e.childNodes.length == 0 ? "" : e.childNodes[0].nodeValue;
                        }
                        tinymce.get('edit_description').setContent(htmlDecode(html.data.description));
                    }
                    $('#edit_start_date').val(html.data.start_date);
                    $('#edit_end_date').val(html.data.end_date);
                    $('#edit_task_hour').val(html.data.task_hour);

                    $('#hidden_id').val(html.data.id);
                    $('#editModal').modal('show');
                }
            })
        });


        let delete_id;

        $(document).on('click', '.delete', function () {
            delete_id = $(this).attr('id');
            $('#confirmModal').modal('show');
            $('.modal-title').text("{{__('DELETE Record')}}");
            $('#ok_button').text("{{__('OK')}}");

        });


        $('.close').on('click', function () {
            $('#sample_form')[0].reset();
            $('#edit_sample_form')[0].reset();
            $('select').selectpicker('refresh');
            $('#task-table').DataTable().ajax.reload();
        });

        $('#ok_button').on('click', function () {
            let target = "{{ route('tasks.index') }}/" + delete_id + '/delete';
            $.ajax({
                url: target,
                beforeSend: function () {
                    $('#ok_button').text("{{__('Deleting...')}}");
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
                        $('#task-table').DataTable().ajax.reload();
                    }, 2000);
                }
            })
        });


    })(jQuery);
</script>
@endpush
