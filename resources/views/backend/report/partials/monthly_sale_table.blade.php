<div class="table-responsive mt-4">
    <table class="table table-bordered" style="border-top: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
        <thead>
            <tr>
                <th><a href="{{url('report/monthly_sale/'.($year-1))}}"><i class="ti ti-arrow-left"></i> {{__('db.Previous')}}</a></th>
                <th colspan="10" class="text-center">{{$year}}</th>
                <th><a href="{{url('report/monthly_sale/'.($year+1))}}">{{__('db.Next')}} <i class="ti ti-arrow-right"></i></a></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>January</strong></td>
                <td><strong>February</strong></td>
                <td><strong>March</strong></td>
                <td><strong>April</strong></td>
                <td><strong>May</strong></td>
                <td><strong>June</strong></td>
                <td><strong>July</strong></td>
                <td><strong>August</strong></td>
                <td><strong>September</strong></td>
                <td><strong>October</strong></td>
                <td><strong>November</strong></td>
                <td><strong>December</strong></td>
            </tr>
            <tr>
                @foreach($total_discount as $key => $discount)
                <td>
                    @if($discount > 0)
                    <strong>{{__("db.Product Discount")}}</strong><br>
                    <span>{{$discount}}</span><br><br>
                    @endif
                    @if($order_discount[$key] > 0)
                    <strong>{{__("db.Order Discount")}}</strong><br>
                    <span>{{$order_discount[$key]}}</span><br><br>
                    @endif
                    @if($total_tax[$key] > 0)
                    <strong>{{__("db.Product Tax")}}</strong><br>
                    <span>{{$total_tax[$key]}}</span><br><br>
                    @endif
                    @if($order_tax[$key] > 0)
                    <strong>{{__("db.Order Tax")}}</strong><br>
                    <span>{{$order_tax[$key]}}</span><br><br>
                    @endif
                    @if($shipping_cost[$key] > 0)
                    <strong>{{__("db.Shipping Cost")}}</strong><br>
                    <span>{{$shipping_cost[$key]}}</span><br><br>
                    @endif
                    @if($total[$key] > 0)
                    <strong>{{__("db.grand total")}}</strong><br>
                    <span>{{$total[$key]}}</span><br>
                    @endif
                </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>