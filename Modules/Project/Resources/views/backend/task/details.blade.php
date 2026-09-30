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

        <div id="formModal" class="modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Status')}}</h5>
                        <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><span
                                    aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        <span id="bug__status_form_result"></span>
                        <form method="post" id="bug_status_form" class="form-horizontal">

                            @csrf
                            <div class="row">

                                <div class="col-md-6 form-group">
                                    <label>{{__('db.Status')}}</label>
                                    <select name="bug_status" id="bug_status" class="form-control selectpicker "
                                            data-live-search="true" data-live-search-style="contains"
                                            title='{{__('Selecting',['key'=>__('db.Status')])}}...'>
                                        <option value="pending">{{__('db.Pending')}}</option>
                                        <option value="solved">{{__('db.solved')}}</option>
                                    </select>
                                </div>

                                <div class="container">
                                    <div class="form-group" align="center">
                                        <input type="hidden" name="action" id="action"/>
                                        <input type="hidden" name="bug_status_id" id="bug_status_id"/>
                                        <input type="submit" name="action_button" id="action_button"
                                               class="btn btn-warning"
                                               value='{{__('db.update')}}'/>
                                    </div>
                                </div>


                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>


        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between collapse-head" 
                            data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="true" aria-controls="collapseExample">
                                <h3>{{__('Task Details')}}</h3>
                                <span class="show btn btn-light btn-sm disabled"><i class="ti ti-chevron-up"></i></span>
                            </div>
                            <div class="collapse show" id="collapseExample">
                                <div class="table-responsive">
                                    <table id="project_details" class="table mb-0">
                                        <thead>
                                            <th scope="row">{{__('db.Title')}}</th>
                                            <th scope="row">{{__('db.project')}}</th>
                                            <th scope="row">{{__('db.Created By')}}</th>
                                            <th scope="row">{{__('db.Start Date')}}</th>
                                            <th scope="row">{{__('db.End Date')}}</th>
                                            <th scope="row">{{__('Estimated Hour')}}</th>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><h6 class="mb-0">{{$task->task_name}}</h6></td>
                                                <td>{{$task->project->title}}</td>
                                                <td>{{$task->addedBy->name ?? ''}}</td>
                                                <td>{{$task->start_date}}</td>
                                                <td>{{$task->end_date}}</td>
                                                <td>{{$task->task_hour}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <span id="assigned_result"></span>
                                    <table class="table mb-0">
                                        <thead>
                                            <th scope="row">{{__('db.Employees')}}*</th>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <form method="post" id="assigned_form" class="form-horizontal">
                                                        @csrf
                                                        <div class="input-group">
                                                            <select name="employee_id[]" id="employee_id" class="form-control js-example-responsive"
                                                                    multiple="multiple">
                                                                @foreach($employees as $emp)
                                                                    <option value="{{$emp->id}}">{{$emp->name}}</option>
                                                                @endforeach
                                                            </select>
                                                            <div class="col-md-6 form-group">
                                                                <input type="submit" name="assigned_submit" id="assigned_submit" class="btn btn-success btn-sm" value={{__("db.Save")}}>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-md-12">

                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="discussions-tab" data-toggle="tab" href="#Discussions"
                                       role="tab"
                                       aria-controls="Discussions" data-table="discussion"
                                       aria-selected="true">{{__('db.Discussion')}}</a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" id="progress-tab" data-toggle="tab" href="#Progress" role="tab"
                                       aria-controls="Progress" data-table="progress"
                                       aria-selected="false">{{__('db.Progress')}}</a>
                                </li>


                                <li class="nav-item">
                                    <a class="nav-link" id="files-tab" data-toggle="tab" href="#Files" role="tab"
                                       aria-controls="Files" data-table="files"
                                       aria-selected="false">{{__('db.Files')}}</a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" id="notes-tab" data-toggle="tab" href="#Notes" role="tab"
                                       aria-controls="Notes" aria-selected="false">{{__('db.Notes')}}</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="Discussions" role="tabpanel"
                                     aria-labelledby="discussions-tab">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <span id="discussions_result"></span>
                                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseDiscussion" role="button" aria-expanded="false" aria-controls="collapseDiscussion">
                                                {{__('Post A Message')}}
                                            </a>
                                            <div class="collapse" id="collapseDiscussion">
                                                <hr>
                                                <form method="post" id="discussions_form" class="form-horizontal" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="form-group">
                                                        <label>{{__('db.Discussion')}}</label>
                                                        <textarea required class="form-control" id="task_discussions" name="task_discussions" rows="3"></textarea>
                                                    </div>

                                                    <div class="form-group">
                                                        <input type="submit" name="discussions_submit" id="discussions_submit" class="btn btn-success" value={{__("db.Save")}}>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table id="discussions-table" class="table ">
                                                <thead>
                                                <tr>
                                                    <th>{{__('db.User')}}</th>
                                                    <th>{{__('db.Message')}}</th>
                                                    <th class="not-exported text-right">{{__('db.action')}}</th>
                                                </tr>
                                                </thead>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                                <div class="tab-pane fade" id="Progress" role="tabpanel" aria-labelledby="progress-tab">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <span id="progress_result"></span>
                                            <form method="post" id="progress_form" class="form-horizontal">
                                                @csrf
                                                <div class="col-md-6 form-group show-edit">
                                                    <label>{{__('db.Status')}}</label>
                                                    <select name="task_status" id="task_status"
                                                            class="form-control selectpicker "
                                                            data-live-search="true" data-live-search-style="contains"
                                                            title='{{__('Selecting',['key'=>__('db.Status')])}}...'>
                                                        <option value="not started">Not Started</option>
                                                        <option value="ongoing">Ongoing</option>
                                                        <option value="completed">{{__('db.Completed')}}</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <label>{{__('Estimated Hour')}} *</label>
                                                    <input type="text" name="task_hour" id="task_hour" required
                                                           class="form-control"
                                                           value="{{$task->task_hour}}"
                                                           placeholder="{{__('Estimated Hour')}}">
                                                </div>

                                                <div class="col-md-6 form-group">
                                                    <input type="submit" name="project_progress_submit"
                                                           id="project_progress_submit"
                                                           class="btn btn-success" value={{__("db.Save")}}>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>


                                <div class="tab-pane fade" id="Files" role="tabpanel" aria-labelledby="files-tab">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <span id="files_result"></span>
                                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseFile" role="button" aria-expanded="false" aria-controls="collapseFile">
                                                {{__('Insert A File')}}
                                            </a>
                                            <div class="collapse" id="collapseFile">
                                                <hr>
                                                <form method="post" id="files_form" class="form-horizontal"
                                                  enctype="multipart/form-data">
                                                    @csrf

                                                    <div class="col-md-6 form-group">
                                                        <label>{{__('db.Title')}} *</label>
                                                        <input type="text" name="file_title" id="file_title" required
                                                            class="form-control"
                                                            placeholder="{{__('db.Title')}}">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>{{__('db.Description')}}</label>
                                                            <textarea class="form-control" id="file_description"
                                                                    name="file_description" rows="3"></textarea>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 form-group">
                                                        <label>{{__('db.attachment')}} </label>
                                                        <input type="file" name="file_attachment" id="file_attachment"
                                                            class="form-control">
                                                    </div>

                                                    <div class="col-md-6 form-group">
                                                        <input type="submit" name="file_submit" id="file_submit"
                                                            class="btn btn-success" value={{__("db.Save")}}>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table id="files-table" class="table ">
                                                <thead>
                                                <tr>
                                                    <th>{{__('db.Title')}}</th>
                                                    <th>{{__('db.Description')}}</th>
                                                    <th>{{__('Date and Time')}}</th>
                                                    <th class="not-exported text-right">{{__('db.action')}}</th>
                                                </tr>
                                                </thead>
                                            </table>
                                        </div>

                                    </div>
                                </div>


                                <div class="tab-pane fade" id="Notes" role="tabpanel" aria-labelledby="notes-tab">
                                    <div class="row">
                                        <div class="col-md-10">
                                            <span id="note_result"></span>
                                            <form method="post" id="note_form" class="form-horizontal">
                                                @csrf
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label>{{__('Project Note')}}</label>
                                                        <textarea required class="form-control" id="task_note"
                                                                  name="task_note"
                                                                  rows="3">{{$task->task_note}}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <input type="submit" name="task_note_submit"
                                                           id="task_note_submit"
                                                           class="btn btn-success" value={{__("db.Save")}}>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </section>



@endsection

@push('scripts')
    @include('backend.layout.partials.datatable_js')
<script>
{!! file_get_contents(Module::find('Project')->getPath(). "/assets/js/ion.rangeSlider.min.js") !!}
</script>
<script type="text/javascript">
    (function($) {
        "use strict";

        let task_status = <?php echo json_encode($task->task_status) ?>;
        let assigned = <?php echo json_encode($name) ?>;
        let description = <?php echo json_encode($task->description) ?>;

        function htmlDecode(input){
            var e = document.createElement('div');
            e.innerHTML = input;
            return e.childNodes.length == 0 ? "" : e.childNodes[0].nodeValue;
        }


        $('#task_status').val(task_status);
        $('#task_description').html(htmlDecode(description));
        $('#employee_id').val(assigned).trigger('change');
        
        setTimeout(function() {
            $('#task_status').selectpicker('refresh');
            $('#employee_id').selectpicker('refresh');
        }, 100);


        $(document).ready(function () {

            let date = $('.date');
            date.datepicker({
                format: "{{ env('Date_Format_JS')}}",
                autoclose: true,
                todayHighlight: true
            });

            $('#assigned_form').on('submit', function (event) {
                event.preventDefault();

                $.ajax({
                    url: "{{ route('tasks.assigned',$task) }}",
                    method: "POST",
                    data: new FormData(this),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    success: function (data) {
                        let html = '';
                        if (data.errors) {
                            html = '<div class="alert alert-danger">';
                            for (let count = 0; count < data.errors.length; count++) {
                                html += '<p>' + data.errors[count] + '</p>';
                            }
                            html += '</div>';
                        }
                        if (data.success) {
                            html = '<div class="alert alert-success">' + data.success + '</div>';
                        }
                        $('#assigned_result').html(html).slideDown(300).delay(5000).slideUp(300);
                    }
                })

            });
        });

        $('#progress_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('task_progress.store',$task) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    $('#progress_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $('[data-table="discussion"]').one('click', function (e) {

            $('#discussions-table').DataTable().clear().destroy();

            let table_table = $('#discussions-table').DataTable({
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
                    url: "{{ route('task_discussions.index',$task) }}",
                    method: "post"
                },

                columns: [


                    {
                        data: 'user',
                        name: 'user'
                    },
                    {
                        data: 'message',
                        name: 'message',
                        render: function (data, type, row) {
                            return data + ' (' + row.created_at + ')';
                        }

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
                        'targets': [2],
                    },
                    {
                        "className": "text-right",
                        "targets": [2]
                    }
                ],

                'select': {style: 'multi', selector: 'td:first-child'},
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        });

        $('#discussions_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('task_discussions.store',$task) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#discussions_form')[0].reset();
                        $('#discussions-table').DataTable().ajax.reload();
                    }
                    $('#discussions_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });


        $('[data-table="files"]').one('click', function (e) {

            $('#files-table').DataTable().clear().destroy();

            let table_table = $('#files-table').DataTable({
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
                    url: "{{ route('task_files.index',$task) }}",
                    method: "post"
                },

                columns: [


                    {
                        data: 'file_title',
                        name: 'file_title'
                    },
                    {
                        data: 'file_description',
                        name: 'file_description',

                    },
                    {
                        data: 'created_at',
                        name: 'created_at',

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
                        'targets': [3],
                    },
                    {
                        "className": "text-right",
                        "targets": [3]
                    }
                ],

                'select': {style: 'multi', selector: 'td:first-child'},
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
            });
            new $.fn.dataTable.FixedHeader(table_table);
        });

        $('#files_form').on('submit', function (event) {
            event.preventDefault();
            $.ajax({
                url: "{{ route('task_files.store',$task) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#files_form')[0].reset();
                        $('#files-table').DataTable().ajax.reload();
                    }
                    $('#files_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });


        $('#note_form').on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                url: "{{ route('task_notes.store',$task) }}",
                method: "POST",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                dataType: "json",
                success: function (data) {
                    let html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (let count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                    }
                    $('#note_result').html(html).slideDown(300).delay(5000).slideUp(300);
                }
            })
        });

        $(document).on('click', '.delete-discussion', function () {

            if (confirm("{{__('Delete Selection',['key'=>__('db.discussions')])}}")) {

                let delete_id = $(this).attr('id');
                let target = "{{ route('tasks.index') }}/" + delete_id + '/delete_discussions';
                $.ajax({
                    url: target,
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
                            $('#discussions-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                })
            }

        });

        $(document).on('click', '.delete-file', function () {

            if (confirm("'{{__('Delete Selection',['key'=>__('db.Files')])}}'")) {

                let delete_id = $(this).attr('id');
                let target = "{{ route('tasks.index') }}/" + delete_id + '/delete_files';
                $.ajax({
                    url: target,
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
                            $('#files-table').DataTable().ajax.reload();
                        }, 2000);
                    }
                })
            }

        });

        $('#discussions-tab').trigger('click');
    })(jQuery);
</script>
@endpush
