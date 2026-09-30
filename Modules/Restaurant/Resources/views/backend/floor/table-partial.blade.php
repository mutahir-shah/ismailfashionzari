<div id="tableModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-name">{{__('db.Add Table')}}</h5>
                <button type="button" data-dismiss="modal" id="close" aria-label="Close" class="close"><span
                            aria-hidden="true">×</span></button>
            </div>

            <div class="modal-body">
                <form id="add-table-form" method="post" action="{{route('tables.store')}}" class="form-horizontal">

                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.name')}} *</label>
                            <input type="text" name="name" class="form-control" placeholder="Type table name..." required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Number of Person')}} *</label>
                            <input type="number" name="number_of_person" class="form-control" required>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{__('db.Description')}}</label>
                            <textarea class="form-control" name="description" rows="5"></textarea>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label></label><br>
                                <input type="hidden" name="floor_id" value=""/>
                                <input type="submit" name="action_button" id="add-table" class="btn btn-success"
                                        value="{{__('db.Save')}}">
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>