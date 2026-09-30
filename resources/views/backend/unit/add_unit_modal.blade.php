    <!-- start unit modal -->
    <div id="createUnitModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
      <div role="document" class="modal-dialog">
          <div class="modal-content">
              <form action="{{ route('unit.store') }}" method="POST">
                  @csrf
              <div class="modal-header">
                  <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Unit')}}</h5>
                  <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
              </div>
              <div class="modal-body">
                  <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                  <form>
                      <div class="form-group">
                      <label>{{__('db.Code')}} *</label>
                      <input type="text" name="unit_code" class="form-control" required>
                      </div>
                      <div class="form-group">
                          <label>{{__('db.name')}} *</label>
                          <input type="text" name="unit_name" class="form-control" required>
                      </div>
                      <div class="form-group">
                          <label>{{__('db.Base Unit')}}</label>
                          <select class="form-control selectpicker" id="base_unit_create" name="base_unit">
                              <option value="">No Base Unit</option>
                              @foreach($lims_unit_list as $unit)
                                  @if($unit->base_unit==null)
                                  <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                  @endif
                              @endforeach
                          </select>
                      </div>
                      <div class="form-group operator">
                          <label>{{ __('db.Operator') }}</label>
                          <select name="operator" class="form-control">
                              <option value="">{{ __('Select an operator') }}</option>
                              <option value="*" {{ old('operator') == '*' ? 'selected' : '' }}>*</option>
                              <option value="/" {{ old('operator') == '/' ? 'selected' : '' }}>/</option>
                          </select>
                      </div>
                      <div class="form-group operation_value">
                          <label>{{__('db.Operation Value')}}</label><input type="number" name="operation_value" placeholder="{{ __('db.Enter operation value') }}" class="form-control" step="any"/>
                      </div>
                      <div class="form-text text-muted mt-2 mb-4">
                          <strong>Example conversions:</strong><br>
                          1 Dozen = 1<strong>*</strong>12 Piece<br>
                          1 Gram = 1<strong>/</strong>1000 KG
                      </div>

                      <input type="submit" id="create_unit" value="{{__('db.submit')}}" class="btn btn-primary">
              </form>
          </div>
          </form>
      </div>
    </div>
    <!-- end unit modal -->