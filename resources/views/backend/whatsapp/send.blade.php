@php
    $asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
@endphp
@extends('backend.layout.main')
@section('content')
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible text-center">
            <button type="button" class="close" data-dismiss="alert"
                aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4>{{ __('db.send_whatsapp_message') }}</h4>
                </div>
                <div class="card-body">
                    {{-- Form Start --}}
                    <form method="POST" action="{{ route('whatsapp.send') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            {{-- Receiver --}}
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.receiver') }}</label>
                                <select name="receiver_phone[]" class="form-control selectpicker" data-live-search="true"
                                    multiple required>
                                    @foreach ($receivers as $group => $items)
                                        <optgroup label="{{ $group }}">
                                            <!-- "Select All" option for functionality, not submitted -->
                                            <option class="select-all text-success" data-group="{{ $group }}"
                                                value="">{{ __('db.Select All') }}</option>

                                            @foreach ($items as $receiver)
                                                <option value="{{ preg_replace('/\D/', '', $receiver->phone) }}"
                                                    @if($selectedPhone && $selectedGroup)
                                                        {{ $selectedGroup == $group && $selectedPhone == preg_replace('/\D/', '', $receiver->phone) ? 'selected' : '' }}
                                                    @else
                                                        {{ $group == array_key_first($receivers) && $loop->first ? 'selected' : '' }}
                                                    @endif
                                                    >
                                                    {{ $receiver->name }} ({{ $receiver->phone }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Template --}}
                            <div class="col-md-6 form-group">
                                <label>{{ __('db.template') }}</label>
                                <select name="template_info" class="form-control selectpicker" data-live-search="true">
                                    <option value="">{{ __('db.no_template_type_message') }}</option>
                                    @foreach ($templates as $tpl)
                                        <option value="{{ $tpl['name'] }}|{{ $tpl['language'] }}">{{ $tpl['name'] }}
                                            ({{ $tpl['language'] }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Message --}}
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.message') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-outline-secondary emoji-btn"
                                            data-emoji-picker="true">😊</button>
                                    </div>
                                    <textarea id="messageInput" name="message" class="form-control" rows="3"
                                        placeholder="{{ __('db.type_your_message') }}"></textarea>
                                </div>
                            </div>

                            <!-- Attachment -->
                            <div class="col-md-12 form-group">
                                <label>{{ __('db.attachment') }}</label><br>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary mr-2" id="attachImageBtn">
                                        {{ __('db.image') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="attachDocBtn">
                                        {{ __('db.document') }}
                                    </button>
                                </div>
                                <input type="file" name="attachment" id="attachmentInput" class="d-none">
                                <input type="hidden" name="attachment_type" id="attachmentType" value="">
                                <span id="fileName" class="ms-2 text-muted"></span>
                            </div>
                            {{-- Submit Button --}}
                            <div class="col-md-12 mt-3">
                                <input type="hidden" name="_from_form" value="1">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('db.send') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    {{-- Form End --}}
                </div>
            </div>
        </div>
    </section>
@endsection


@push('scripts')
    <script type="text/javascript" src="{{ asset($asset_prefix . 'vendor/emoji/vanillaEmojiPicker.js') }}"></script>

    <script type="text/javascript">

        $(document).ready(function() {

            function toggleMessageAttachment() {
                const templateSelected = $('select[name="template_info"]').val() != '';
                if (templateSelected) {
                    // Hide message and attachment
                    $('#messageInput').closest('.form-group').hide();
                    $('#attachmentInput').closest('.form-group').hide();
                } else {
                    // Show message and attachment
                    $('#messageInput').closest('.form-group').show();
                    $('#attachmentInput').closest('.form-group').show();
                }
            }

            // Initial check on page load
            toggleMessageAttachment();

            // On template change
            $('select[name="template_info"]').on('change', function() {
                toggleMessageAttachment();
            });

            // Initialize Vanilla Emoji Picker for all elements with data-emoji-picker="true"
            new EmojiPicker({
                trigger: [{
                    selector: '.emoji-btn',
                    insertInto: ['#messageInput'] // target textarea ID
                }],
                closeButton: true,
                specialButtons: '#6244a6'
            });

            $('#attachImageBtn').on('click', function() {
                $('#attachmentType').val('image');
                $('#attachmentInput').attr('accept', '.jpg,.jpeg,.png,.gif,.webp');
                $('#attachmentInput').click();
            });

            $('#attachDocBtn').on('click', function() {
                $('#attachmentType').val('document');
                $('#attachmentInput').removeAttr('accept'); // allow any file type
                $('#attachmentInput').click();
            });

            $('#attachmentInput').on('change', function() {
                const file = this.files[0];
                if (file) {
                    $('#fileName').text(file.name);
                } else {
                    $('#fileName').text('');
                    $('#attachmentType').val('');
                }
            });

            $('.selectpicker').on('changed.bs.select', function(e, clickedIndex, isSelected, previousValue) {
                var $select = $(this);
                var $options = $select.find('option');
                var clickedOption = $options.eq(clickedIndex);

                if (clickedOption.hasClass('select-all')) {
                    var groupLabel = clickedOption.data('group');

                    // Find all options in the group except 'Select All'
                    var $groupOptions = $options.filter(function() {
                        return $(this).parent('optgroup').attr('label') === groupLabel && !$(this)
                            .hasClass('select-all');
                    });

                    // Check if all are already selected
                    var allSelected = $groupOptions.length === $groupOptions.filter(':selected').length;

                    // Toggle selection
                    $groupOptions.prop('selected', !allSelected);

                    // Deselect "Select All" itself
                    clickedOption.prop('selected', false);

                    // Refresh UI
                    $select.selectpicker('refresh');
                }
            });
        });
    </script>
@endpush
