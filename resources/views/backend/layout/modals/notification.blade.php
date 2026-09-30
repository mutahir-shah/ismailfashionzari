        <div id="notification-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">{{ __('Send Notification') }}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                                aria-hidden="true"><i class="ti ti-x"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic">
                        <form action="{{ route('notifications.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                        <div class="row">
                            <?php
                            $lims_user_list = DB::table('users')
                                ->where([['is_active', true], ['id', '!=', \Auth::id() ?? 0]])
                                ->get();
                            ?>
                            <div class="col-md-4 form-group">
                                <input type="hidden" name="sender_id" value="{{ \Auth::id() }}">
                                <label>{{ __('User') }} *</label>
                                <select name="receiver_id" class="selectpicker form-control" required
                                    data-live-search="true" data-live-search-style="begins" title="Select user...">
                                    @foreach ($lims_user_list as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name . ' (' . $user->email . ')' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ __('Reminder Date') }}</label>
                                <input type="text" name="reminder_date" class="form-control date"
                                    value="{{ date('d-m-Y') }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ __('Attach Document') }}</label>
                                <input type="file" name="document" class="form-control">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ __('Message') }} *</label>
                                <textarea rows="5" name="message" class="form-control" required></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary ">{{ __('submit') }}</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
