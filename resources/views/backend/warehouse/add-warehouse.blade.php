<div id="addWarehouse" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
  <div role="document" class="modal-dialog">
    <div class="modal-content">
        <form action="{{ route('warehouse.store') }}" method="POST" id="warehouse-form">
            @csrf
      <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Warehouse')}}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti it-x"></i></span></button>
      </div>
      <div class="modal-body">
        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
          <div class="form-group">
            <label>{{__('db.name')}} *</label>
            <input type="text" placeholder="{{ __('db.Type WareHouse Name') }}" name="name" required="required" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Phone Number')}} *</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label>{{__('db.email')}}</label>
            <input type="email" name="email" placeholder="example@example.com" class="form-control">
          </div>
          <div class="form-group">
            <label>{{__('db.Address')}} *</label>
            <textarea required class="form-control" rows="3" name="address"></textarea>
          </div>
          <div class="form-group">
              <label><strong>{{__('db.POS Type')}}</strong></label>
              <select name="pos_type" class="form-control selectpicker">
                  <option value="regular">{{__('db.Regular POS')}}</option>
                  <option value="restaurant">{{__('db.Restaurant POS')}}</option>
                  <option value="both">{{__('db.Regular + Restaurant')}}</option>
              </select>
          </div>
          <div class="form-group">
            <input type="submit" value="{{__('db.submit')}}" class="btn btn-primary warehouse-submit-btn">
          </div>
      </div>
      </form>
    </div>
  </div>
</div>