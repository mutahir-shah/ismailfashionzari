<div class="table-responsive mb-4" id="report-table-container">
    <table id="report-table" class="table table-hover">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{__('db.date')}}</th>
                <th>{{__('db.reference')}}</th>
                <th>{{__('db.Supplier Details')}}</th>
                <th>{{__('db.grand total')}}</th>
                <th>{{__('db.Returned Amount')}}</th>
                <th>{{__('db.Paid')}}</th>
                <th>{{__('db.Due')}}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lims_purchase_data as $key => $purchase_data)
                @if($purchase_data->supplier_id)
                <?php
                    $supplier = DB::table('suppliers')->find($purchase_data->supplier_id);
                    $returned_amount = DB::table('return_purchases')
                        ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
                        ->where('return_purchases.purchase_id', $purchase_data->id)
                        ->sum(DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total)'));
                ?>
                <tr>
                    <td>{{$key}}</td>
                    <td>{{date(gen_setting()->date_format, strtotime($purchase_data->updated_at->toDateString())) . ' '. $purchase_data->updated_at->toTimeString()}}</td>
                    <td>{{$purchase_data->reference_no}}</td>
                    <td>{{$supplier->name .' (' .$supplier->phone_number . ')'}}</td>
                    <td>{{number_format((float)$purchase_data->grand_total, gen_setting()->decimal, '.', '')}}</td>
                    <td>{{number_format((float)$returned_amount, gen_setting()->decimal, '.', '')}}</td>
                    @if($purchase_data->paid_amount)
                    <td>{{number_format((float)$purchase_data->paid_amount, gen_setting()->decimal, '.', '')}}</td>
                    @else
                    <td>{{number_format(0, gen_setting()->decimal, '.', '')}}</td>
                    @endif
                    <td>{{number_format((float)($purchase_data->grand_total - $returned_amount - $purchase_data->paid_amount), gen_setting()->decimal, '.', '')}}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot class="tfoot active">
            <th></th>
            <th>{{__('db.Total')}}:</th>
            <th></th>
            <th></th>
            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
            <th>{{number_format(0, gen_setting()->decimal, '.', '')}}</th>
        </tfoot>
    </table>
</div>
