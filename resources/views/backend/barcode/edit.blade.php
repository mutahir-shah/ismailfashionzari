@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>{{__('db.Edit Barcode Sticker Setting')}}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ action([\App\Http\Controllers\BarcodeController::class, 'update'], [$barcode->id]) }}" method="POST" id="add_barcode_settings_form">
                            @csrf
                            @method('PUT')
                            <div class="box box-solid">
                                <div class="box-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="name">{{ __('db.Sticker Sheet setting Name') }} (*):</label>
                                        <input type="text" name="name" value="{{ $barcode->name }}" class="form-control" required placeholder="{{ __('db.Sticker Sheet setting Name') }}">
                                    </div>
                                    </div>
                                    <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="description">{{ __('db.Sticker Sheet setting Description') }}:</label>
                                        <textarea name="description" class="form-control" placeholder="{{ __('db.Sticker Sheet setting Description') }}" rows="3">{{ $barcode->description }}</textarea>
                                    </div>
                                    </div>
                                    <div class="col-sm-12">

                                    <div class="form-group">
                                        <label>@lang('Label Layout')</label>
                                        <select name="layout_type" id="layout_type" class="form-control">
                                            <option value="sheet" {{ $barcode->layout_type == 'sheet' ? 'selected' : '' }}>
                                                @lang('Sheet')
                                            </option>
                                            <option value="continuous" {{ $barcode->layout_type == 'continuous' ? 'selected' : '' }}>
                                                @lang('Continuous Roll')
                                            </option>
                                            <option value="dumbbell" {{ $barcode->layout_type == 'dumbbell' ? 'selected' : '' }}>
                                                @lang('Dumbbell')
                                            </option>
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
                                        <input type="number" name="top_margin" value="{{ $barcode->top_margin }}" class="form-control" placeholder="{{ __('db.top_margin') }}" min="0" step="0.00001" required>
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
                                        <input type="number" name="left_margin" value="{{ $barcode->left_margin }}" class="form-control" placeholder="{{ __('db.Additional left margin') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="width">{{ __('db.Width of sticker') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="width" value="{{ $barcode->width }}" class="form-control" placeholder="{{ __('db.Width of sticker') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="height">{{ __('db.Height of sticker') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="height" value="{{ $barcode->height }}" class="form-control" placeholder="{{ __('db.Barcode Height') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="paper_width">{{ __('db.Paper width') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="paper_width" value="{{ $barcode->paper_width }}" class="form-control" placeholder="{{ __('db.Paper width') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div id="paper-height-section" class="col-sm-6 paper_height_div">
                                    <div class="form-group">
                                        <label for="paper_height">{{ __('db.Paper height') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="paper_height" value="{{ $barcode->paper_height }}" class="form-control" placeholder="{{ __('db.Paper height') }}" min="0.1" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="stickers_in_one_row">{{ __('db.Stickers in one row') }}:*</label>
                                        <div class="input-group">

                                        <input type="number" name="stickers_in_one_row" value="{{ $barcode->stickers_in_one_row }}" class="form-control" placeholder="{{ __('db.Stickers in one row') }}" min="1" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="row_distance">{{ __('db.Distance between two rows') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="row_distance" value="{{ $barcode->row_distance }}" class="form-control" placeholder="{{ __('db.Distance between two rows') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="col_distance">{{ __('db.Distance between two columns') }} ({{ __('db.In Inches') }}):*</label>
                                        <div class="input-group">

                                        <input type="number" name="col_distance" value="{{ $barcode->col_distance }}" class="form-control" placeholder="{{ __('db.Distance between two columns') }}" min="0" step="0.00001" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6 stickers_per_sheet_div">
                                    <div class="form-group">
                                        <label for="stickers_in_one_sheet">{{ __('db.No of Stickers per sheet') }}:*</label>
                                        <div class="input-group">

                                        <input type="number" name="stickers_in_one_sheet" value="{{ $barcode->stickers_in_one_sheet }}" class="form-control" placeholder="{{ __('db.No of Stickers per sheet') }}" min="1" required>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="col-sm-6"></div>
                                    <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="">
                                        <label>
                                            <input type="checkbox" name="is_default" value="1" {{ $barcode->is_default ? 'checked' : '' }}> @lang('db.Set as default')</label>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="clearfix"></div>

                                    <div class="col-sm-12 text-center">
                                    <button type="submit" class="btn btn-primary btn-big">{{ __('db.update') }}</button>
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

