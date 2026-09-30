@extends('backend.layout.main')
@section('content')
<div class="container-fluid">
    <div class="card mt-3">
        <div class="card-header">
            <h3>Notification Channel Settings Control Matrix</h3>
        </div>
        <div class="card-body">
            @if(session()->has('message'))
            <div class="alert alert-success">{{ session()->get('message') }}</div>
            @endif

            <form action="{{ route('notifications.settings.update') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Event Channel Trigger</th>
                                <th>In-App</th>
                                <th>WhatsApp</th>
                                <th>SMS</th>
                                <th>Email</th>
                                <th style="width: 200px;">Target Recipients</th>
                                <th>Custom Message Templates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings as $setting)
                            @php
                            // Ensure recipients is always treated as an array
                            $currentRecipients = is_array($setting->recipients) ? $setting->recipients : json_decode($setting->recipients, true) ?? [];
                            @endphp
                            <tr>
                                <td><strong>{{ ucwords(str_replace('_', ' ', $setting->event)) }}</strong></td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="settings[{{ $setting->id }}][notify_in_app]" value="1" class="custom-control-input" id="switch_in_app_{{ $setting->id }}" {{ $setting->notify_in_app ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="switch_in_app_{{ $setting->id }}"></label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="settings[{{ $setting->id }}][notify_whatsapp]" value="1" class="custom-control-input" id="switch_whatsapp_{{ $setting->id }}" {{ $setting->notify_whatsapp ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="switch_whatsapp_{{ $setting->id }}"></label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="settings[{{ $setting->id }}][notify_sms]" value="1" class="custom-control-input" id="switch_sms_{{ $setting->id }}" {{ $setting->notify_sms ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="switch_sms_{{ $setting->id }}"></label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="settings[{{ $setting->id }}][notify_mail]" value="1" class="custom-control-input" id="switch_email_{{ $setting->id }}" {{ $setting->notify_mail ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="switch_email_{{ $setting->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <select name="settings[{{ $setting->id }}][recipients][]" class="form-control" multiple style="height: 90px;">
                                        <option value="admin" {{ in_array('admin', $currentRecipients) ? 'selected' : '' }}>Admins</option>
                                        <option value="customer" {{ in_array('customer', $currentRecipients) ? 'selected' : '' }}>Customers</option>
                                        <option value="supplier" {{ in_array('supplier', $currentRecipients) ? 'selected' : '' }}>Suppliers</option>
                                    </select>
                                    <!-- <small class="text-muted d-block mt-1">Hold Ctrl (or Cmd) to select multiple</small> -->
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-success mb-1" data-toggle="modal" data-target="#whatsappModal_{{ $setting->id }}">
                                        Edit WhatsApp
                                    </button>
                                    <div class="modal fade text-left" id="whatsappModal_{{ $setting->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">WhatsApp Message Layout</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea name="settings[{{ $setting->id }}][whatsapp_message]" class="form-control" rows="4">{{ $setting->whatsapp_message }}</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" data-dismiss="modal">Save/Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-info mb-1" data-toggle="modal" data-target="#smsModal_{{ $setting->id }}">
                                        Edit SMS
                                    </button>
                                    <div class="modal fade text-left" id="smsModal_{{ $setting->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">SMS Message Layout</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea name="settings[{{ $setting->id }}][sms_message]" class="form-control" rows="4">{{ $setting->sms_message }}</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" data-dismiss="modal">Save/Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" data-toggle="modal" data-target="#emailModal_{{ $setting->id }}">
                                        Edit Email
                                    </button>
                                    <div class="modal fade text-left" id="emailModal_{{ $setting->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Email Content Layout</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea name="settings[{{ $setting->id }}][mail_message]" class="form-control" rows="4">{{ $setting->mail_message }}</textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-primary" data-dismiss="modal">Save/Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <p class="text-info font-weight-bold mb-2">Available Code Dynamic Fields: <code class="text-dark">[customer] [reference] [amount] [product] [qty]</code></p>
                    <button type="submit" class="btn btn-primary">Save Preference Toggles</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection