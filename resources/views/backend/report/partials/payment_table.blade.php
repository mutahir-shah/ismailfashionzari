<div class="table-responsive mb-4" id="report-table-container">
    <table id="report-table" class="table table-hover">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{ __('db.date') }}</th>
                <th>{{ __('db.Payment Reference') }} </th>
                <th>{{ __('db.Sale Reference') }}</th>
                <th>{{ __('db.Purchase Reference') }}</th>
                <th>{{ __('db.Paid By') }}</th>
                <th>{{ __('db.Amount') }}</th>
                <th>{{ __('db.Created By') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lims_payment_data as $payment)
                <?php
                $sale = DB::table('sales')->find($payment->sale_id);
                $purchase = DB::table('purchases')->find($payment->purchase_id);
                $user = DB::table('users')->find($payment->user_id);
                ?>
                <tr>
                    <td></td>
                    <td>{{ date(gen_setting()->date_format, strtotime($payment->created_at->toDateString())) . ' ' . $payment->created_at->toTimeString() }}
                    </td>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>
                        @if ($sale)
                            {{ $sale->reference_no }}
                        @endif
                    </td>
                    <td>
                        @if ($purchase)
                            {{ $purchase->reference_no }}
                        @endif
                    </td>
                    <td>{{ $payment->paying_method }}</td>
                    <td>{{ $payment->amount }}</td>
                    <td>{{ $user->name }}<br>{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
        <!-- <tfoot class="tfoot active">
            <th></th>
            <th>{{ __('db.Total') }}:</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th>{{ number_format(0, gen_setting()->decimal, '.', '') }}</th>
            <th></th>
        </tfoot> -->
    </table>
</div>
