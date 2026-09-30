        <div id="category-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('category.store') }}" method="post" enctype="multipart/form-data" id="category-form">
                    @csrf
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Add Category') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                            <small>{{ __('The field labels marked with are required input fields') }}.</small></p>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>{{ __('name') }} *</label>
                                <input type="text" name="name" class="form-control" required placeholder="{{ __('db.Type category name') }}">
                                <x-validation-error fieldName="name" />
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Image') }}</label>
                                <input type="file" name="image" class="form-control">
                                <x-validation-error fieldName="image" />
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ __('Parent Category') }}</label>
                                <select name="parent_id" class="form-control selectpicker" id="parent">
                                    <option value="">No {{ __('parent') }}</option>
                                    @foreach (get_active_categories() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-validation-error fieldName="parent_id" />
                            </div>
                            @if (\Schema::hasColumn('categories', 'woocommerce_category_id'))
                                <div class="col-md-6 form-group mt-4">
                                    <input class="mt-3" name="is_sync_disable" type="checkbox"
                                        id="is_sync_disable" value="1">&nbsp;
                                    {{ __('Disable Woocommerce Sync') }}
                                    <x-validation-error fieldName="is_sync_disable" />
                                </div>
                            @endif

                            @if (in_array('ecommerce', explode(',', gen_setting()->modules)))
                                <div class="col-md-12 mt-3">
                                    <h6><strong>{{ __('For Website') }}</strong></h6>
                                    <hr>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>{{ __('Icon') }}</label>
                                    <input type="file" name="icon" class="form-control">
                                </div>
                                <div class="col-md-6 form-group">
                                    <input class="mt-5" type="checkbox" name="featured" id="featured"
                                        value="1"> <label>{{ __('List on category dropdown') }}</label>
                                </div>
                            @endif
                        </div>

                        @if (in_array('ecommerce', explode(',', gen_setting()->modules)))
                            <div class="row">
                                <div class="col-md-12 mt-3">
                                    <h6><strong>{{ __('For SEO') }}</strong></h6>
                                    <hr>
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Title') }}</label>
                                    <input type="text" name="page_title" class="form-control" placeholder="{{ __('db.Meta Title') }}">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Description') }}</label>
                                    <input type="text" name="short_description" class="form-control" placeholder="{{ __('db.Meta Description') }}">
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <input type="hidden" class="category-ajax-check" name="ajax" value="0">
                            <button type="submit"
                                class="btn btn-primary category-submit-btn">{{ __('submit') }}</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
