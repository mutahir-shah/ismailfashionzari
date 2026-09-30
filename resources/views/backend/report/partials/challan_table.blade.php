<table id="challan-table" class="table table-striped">
    <thead>
        <tr>
            <th class="not-exported"></th>
            <th>Challan No</th>
            <th>Order No</th>
            <th>Order Date</th>
            <th>code</th>
            <th>Delivery Date</th>
            <th>Sales Amount</th>
            <th>Cash Payment</th>
            <th>Online Payment</th>
            <th>Cheque Payment</th>
            <th>Shipping Income</th>
            <th>Delivery Charge</th>
            <th>Net</th>
            <th>Net Cash</th>
        </tr>
    </thead>
    <tbody>
        @foreach($challan_data as $challan)
        <?php
            $packingSlipList = explode(",", $challan->packing_slip_list);
            $status_list = explode(",", $challan->status_list);
            $cash_list = explode(",", $challan->cash_list);
            $cheque_list = explode(",", $challan->cheque_list);
            $online_payment_list = explode(",", $challan->online_payment_list);
            $delivery_charge_list = explode(",", $challan->delivery_charge_list);
        ?>
            @foreach($packingSlipList as  $key => $packingSlipId)
            <?php $packingSlip = \App\Models\PackingSlip::with('sale.products')->find($packingSlipId); ?>
            <?php
                if(!isset($cash_list[$key]) || !$cash_list[$key])
                    $cash_list[$key] = 0;
                if(!isset($online_payment_list[$key]) || !$online_payment_list[$key])
                    $online_payment_list[$key] = 0;
                if(!isset($cheque_list[$key]) || !$cheque_list[$key])
                    $cheque_list[$key] = 0;
                if(!isset($delivery_charge_list[$key]) || !$delivery_charge_list[$key])
                    $delivery_charge_list[$key] = 0;
            ?>
            <tr>
                <td><?php echo $index ?></td>
                <td>DC-{{$challan->reference_no}}</td>
                <td>{{$packingSlip->sale->reference_no ?? '-'}}</td>

                <td>{{date(config('date_format'), strtotime($packingSlip->sale->created_at))}}</td>
                <td>
                    @foreach($packingSlip->sale->products as $i => $product)
                    @if($i),@endif
                    {{$product->code}}
                    @endforeach
                </td>
                <td>
                    @if($packingSlip->sale->sale_status == 1)
                        {{date(config('date_format'), strtotime($packingSlip->sale->updated_at))}}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{$packingSlip->sale->grand_total ?? 0}}</td>
                <td>{{$cash_list[$key]}}</td>
                <td>{{$online_payment_list[$key]}}</td>
                <td>{{$cheque_list[$key]}}</td>
                <td>{{$packingSlip->sale->shipping_cost ?? 0}}</td>
                <td>{{$delivery_charge_list[$key]}}</td>
                <td>{{$cash_list[$key] + $online_payment_list[$key] + $cheque_list[$key] - $delivery_charge_list[$key]}}</td>
                <td>{{$cash_list[$key] - $delivery_charge_list[$key]}}</td>
            </tr>
            <?php $index++; ?>
            @endforeach
        @endforeach
    </tbody>
    <tfoot>
        <th></th>
        <th>Total:</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
    </tfoot>
</table>
