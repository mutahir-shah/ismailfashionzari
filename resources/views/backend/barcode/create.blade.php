@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Add barcode sticker setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ action([\App\Http\Controllers\BarcodeController::class, 'store']) }}" method="post" id="add_barcode_settings_form">
                            @csrf
                            <div class="box box-solid">
                                <div class="box-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                    <div class="form-group">
                                        <input type="hidden" name="is_custom" value="1">
                                        <label for="name">{{ __('db.Sticker Sheet setting Name') }}:*</label>
                                        <input type="text" name="name" class="form-control" required placeholder="{{ __('db.Sticker Sheet setting Name') }}">
                                    </div>
                                    </div>
                                    <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="description">{{ __('db.Sticker Sheet setting Description') }}:</label>
                                        <textarea name="description" class="form-control" placeholder="{{ __('db.Sticker Sheet setting Description') }}" rows="3"></textarea>
                                    </div>
                                    </div>
                                    <div class="col-sm-12">

                                    <div class="form-group">
                                        <label>@lang('Label Layout')</label>
                                        <select name="layout_type" id="layout_type" class="form-control">
                                            <option value="sheet">@lang('Sheet')</option>
                                            <option value="continuous">@lang('Continuous Roll')</option>
                                            <option value="dumbbell">@lang('Dumbbell')</option>
                                        </select>
                                    </div>

                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="top_margin">{{ __('db.Additional top margin') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span>
                                        </span>
                                        <input type="number" name="top_margin" value="0" class="form-control" placeholder="{{ __('db.top_margin') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="left_margin">{{ __('db.Additional left margin') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
                                        </span>
                                        <input type="number" name="left_margin" value="0" class="form-control" placeholder="{{ __('db.Additional left margin') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="width">{{ __('db.Width of sticker') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="width" value="0" class="form-control" placeholder="{{ __('db.Width of sticker') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="height">{{ __('db.Height of sticker') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="height" value="0" class="form-control" placeholder="{{ __('db.Height of sticker') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="paper_width">{{ __('db.Paper width') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="paper_width" value="0" class="form-control" placeholder="{{ __('db.Paper width') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6 paper_height_div">
                                    <div class="form-group">
                                        <label for="paper_height">{{ __('db.Paper height') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="paper_height" value="0" class="form-control" placeholder="{{ __('db.Paper height') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="stickers_in_one_row">{{ __('db.Stickers in one row') }}:*</label>
                                            <div class="input-group">

                                            <input type="number" name="stickers_in_one_row" value="0" class="form-control" placeholder="{{ __('db.Stickers in one row') }}" min="1" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="row_distance">{{ __('db.Distance between two rows') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="row_distance" value="0" class="form-control" placeholder="{{ __('db.Distance between two rows') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="col_distance">{{ __('db.Distance between two columns') }} ({{ __('db.In Inches') }}):*</label>
                                            <div class="input-group">

                                            <input type="number" name="col_distance" value="0" class="form-control" placeholder="{{ __('db.Distance between two columns') }}" min="0" step="0.00001" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6 stickers_per_sheet_div">
                                    <div class="form-group">
                                        <label for="stickers_in_one_sheet">{{ __('db.No of Stickers per sheet') }}:*</label>
                                        <div class="input-group">

                                        <input type="number" name="stickers_in_one_sheet" value="0" class="form-control" placeholder="{{ __('db.No of Stickers per sheet') }}" min="1" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <div class="mt-4">
                                                <label>
                                                <input type="checkbox" name="is_default" value="1"> @lang('db.Set as default') <x-info title="Setting this as default will set it for all future barcode printing." type="info" /></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-big">{{ __('db.Save') }}</button>
                                    </div>
                                </div>
                                </div>
                             </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const layoutTypeSelect = $("#layout_type");

        const toggleFields = () => {
            const layoutType = layoutTypeSelect.val();
            const paperHeightDiv = $(".paper_height_div");
            const paperHeightInput = $("input[name='paper_height']");
            const stickersPerSheetDiv = $(".stickers_per_sheet_div");
            const stickersPerSheetInput = $("input[name='stickers_in_one_sheet']");

            if (layoutType === 'continuous' || layoutType === 'dumbbell') {
                paperHeightInput.val(0);
                paperHeightInput.prop("disabled", true).prop("required", false);
                paperHeightDiv.hide();

                stickersPerSheetInput.val(0);
                stickersPerSheetInput.prop("disabled", true).prop("required", false);
                stickersPerSheetDiv.hide();
            } else {
                paperHeightInput.prop("disabled", false).prop("required", true);
                if (parseFloat(paperHeightInput.val()) === 0) {
                    paperHeightInput.val('');
                }
                paperHeightDiv.show();

                stickersPerSheetInput.prop("disabled", false).prop("required", true);
                if (parseInt(stickersPerSheetInput.val()) === 0) {
                    stickersPerSheetInput.val('');
                }
                stickersPerSheetDiv.show();
            }
        };

        toggleFields();
        layoutTypeSelect.on("change", toggleFields);
    });
</script>
@endpush
