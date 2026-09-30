<div class="container-fluid">
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h3><i class="ti ti-heart"></i> {{__('db.Purchase')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.grand total')}} <span class="float-right"> {{number_format((float)$purchase[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Purchase')}} <span class="float-right">{{$total_purchase}}</span></p>
                        <p class="mt-2">{{__('db.Paid')}} <span class="float-right">{{number_format((float)$purchase[0]->paid_amount, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Tax')}} <span class="float-right">{{number_format((float)$purchase[0]->tax, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Discount')}} <span class="float-right">{{number_format((float)$purchase[0]->discount, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-shopping-cart"></i> {{__('db.Sale')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.grand total')}} <span class="float-right"> {{number_format((float)$sale[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Shipping Cost')}} <span class="float-right"> {{number_format((float)$sale[0]->shipping_cost, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Sale')}} <span class="float-right">{{$total_sale}}</span></p>
                        <p class="mt-2">{{__('db.Paid')}} <span class="float-right">{{number_format((float)$sale[0]->paid_amount, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Tax')}} <span class="float-right">{{number_format((float)$sale[0]->tax, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Discount')}} <span class="float-right">{{number_format((float)$sale[0]->discount, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-random "></i> {{__('db.Sale Return')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.grand total')}} <span class="float-right"> {{number_format((float)$return[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.return')}} <span class="float-right">{{$total_return}}</span></p>
                        <p class="mt-2">{{__('db.Tax')}} <span class="float-right">{{number_format((float)$return[0]->tax, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-random "></i> {{__('db.Purchase Return')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.grand total')}} <span class="float-right"> {{number_format((float)$purchase_return[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.return')}} <span class="float-right">{{$total_purchase_return}}</span></p>
                        <p class="mt-2">{{__('db.Tax')}} <span class="float-right">{{number_format((float)$purchase_return[0]->tax, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3><i class="ti ti-cash-banknote"></i> {{__('db.profit')}} / {{__('db.Loss')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Sale')}} <span class="float-right">{{number_format((float)$sale[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Product Cost')}} <span class="float-right">- {{number_format((float)$product_cost, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Sale Return')}} <span class="float-right">- {{number_format((float)$return[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.profit')}} <span class="float-right"> {{number_format((float)($sale[0]->grand_total - $product_cost - $return[0]->grand_total), gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-cash-banknote "></i> {{__('db.Net Profit')}} / {{__('db.Net Loss')}}</h3>
                    <hr>
                    <h4 class="text-center">{{number_format((float)(($sale[0]->grand_total-$sale[0]->shipping_cost-$sale[0]->tax) - ($product_cost-$product_tax) - ($return[0]->grand_total-$return[0]->tax) - $expense + $income), gen_setting()->decimal, '.', '')}}</h4>
                    <p class="text-center">
                        ({{__('db.Sale')}} {{number_format((float)($sale[0]->grand_total), gen_setting()->decimal, '.', '')}} - {{__('db.Shipping Cost')}} {{number_format((float)($sale[0]->shipping_cost), gen_setting()->decimal, '.', '')}}) - {{__('db.Tax')}} {{number_format((float)($sale[0]->tax), gen_setting()->decimal, '.', '')}}) - ({{__('db.Product Cost')}} {{number_format((float)($product_cost), gen_setting()->decimal, '.', '')}} - {{__('db.Tax')}} {{number_format((float)($product_tax), gen_setting()->decimal, '.', '')}}) - ({{__('db.return')}} {{number_format((float)($return[0]->grand_total), gen_setting()->decimal, '.', '')}} - {{__('db.Tax')}} {{number_format((float)($return[0]->tax), gen_setting()->decimal, '.', '')}}) - ({{__('db.Expense')}} {{number_format((float)($expense), gen_setting()->decimal, '.', '')}}) + ({{__('db.Income')}} {{number_format((float)($income), gen_setting()->decimal, '.', '')}})
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Payment Recieved')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Amount')}} <span class="float-right"> {{number_format((float)$payment_recieved, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Recieved')}} <span class="float-right">{{$payment_recieved_number}}</span></p>
                        <p class="mt-2">Cash <span class="float-right">{{number_format((float)$cash_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Cheque <span class="float-right">{{number_format((float)$cheque_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Credit Card <span class="float-right">{{number_format((float)$credit_card_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Gift Card <span class="float-right">{{number_format((float)$gift_card_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Paypal <span class="float-right">{{number_format((float)$paypal_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Deposit <span class="float-right">{{number_format((float)$deposit_payment_sale, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Payment Sent')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Amount')}} <span class="float-right"> {{number_format((float)$payment_sent, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Recieved')}} <span class="float-right">{{$payment_sent_number}}</span></p>
                        <p class="mt-2">Cash <span class="float-right">{{number_format((float)$cash_payment_purchase, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Cheque <span class="float-right">{{number_format((float)$cheque_payment_purchase, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">Credit Card <span class="float-right">{{number_format((float)$credit_card_payment_purchase, gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Expense')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Amount')}} <span class="float-right"> {{number_format((float)$expense, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Expense')}} <span class="float-right">{{$total_expense}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Income')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Amount')}} <span class="float-right"> {{number_format((float)$income, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Income')}} <span class="float-right">{{$total_income}}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Payroll')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Amount')}} <span class="float-right"> {{number_format((float)$payroll, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Payroll')}} <span class="float-right">{{$total_payroll}}</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <div class="row mt-2">
        <div class="col-md-4 offset-md-4">
            <div class="card">
                <div class="card-body">

                    <h3><i class="ti ti-dollar"></i> {{__('db.Cash in Hand')}}</h3>
                    <hr>
                    <div class="mt-3">
                        <p class="mt-2">{{__('db.Recieved')}} <span class="float-right"> {{number_format((float)($payment_recieved), gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Sent')}} <span class="float-right">- {{number_format((float)($payment_sent), gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Sale Return')}} <span class="float-right">- {{number_format((float)$return[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Purchase Return')}} <span class="float-right"> {{number_format((float)$purchase_return[0]->grand_total, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Expense')}} <span class="float-right">- {{number_format((float)$expense, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.Payroll')}} <span class="float-right">- {{number_format((float)$payroll, gen_setting()->decimal, '.', '')}}</span></p>
                        <p class="mt-2">{{__('db.In Hand')}} <span class="float-right">{{number_format((float)($payment_recieved - $payment_sent - $return[0]->grand_total + $purchase_return[0]->grand_total - $expense - $payroll), gen_setting()->decimal, '.', '')}}</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        @foreach($warehouse_name as $key => $name)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">

                        <h3><i class="ti ti-cash-banknote"></i> {{$name}}</h3>
                        <h4 class="text-center mt-3">{{number_format((float)($warehouse_sale[$key][0]->grand_total - $warehouse_purchase[$key][0]->grand_total - $warehouse_return[$key][0]->grand_total + $warehouse_purchase_return[$key][0]->grand_total), gen_setting()->decimal, '.', '')}}</h4>
                        <p class="text-center">
                            {{__('db.Sale')}} {{number_format((float)($warehouse_sale[$key][0]->grand_total), gen_setting()->decimal, '.', '')}} - {{__('db.Purchase')}} {{number_format((float)($warehouse_purchase[$key][0]->grand_total), gen_setting()->decimal, '.', '')}} - {{__('db.Sale Return')}} {{number_format((float)($warehouse_return[$key][0]->grand_total), gen_setting()->decimal, '.', '')}} + {{__('db.Purchase Return')}} {{number_format((float)($warehouse_purchase_return[$key][0]->grand_total), gen_setting()->decimal, '.', '')}}
                        </p>
                        <hr style="border-color: rgba(0, 0, 0, 0.2);">
                        <h4 class="text-center">{{number_format((float)(($warehouse_sale[$key][0]->grand_total - $warehouse_sale[$key][0]->tax) - ($warehouse_purchase[$key][0]->grand_total - $warehouse_purchase[$key][0]->tax) - ($warehouse_return[$key][0]->grand_total - $warehouse_return[$key][0]->tax) + ($warehouse_purchase_return[$key][0]->grand_total - $warehouse_purchase_return[$key][0]->tax) ), gen_setting()->decimal, '.', '')}}</h4>
                        <p class="text-center">
                            {{__('db.Net Sale')}} {{number_format((float)($warehouse_sale[$key][0]->grand_total - $warehouse_sale[$key][0]->tax), gen_setting()->decimal, '.', '')}} -  {{__('db.Net Purchase')}} {{number_format((float)($warehouse_purchase[$key][0]->grand_total - $warehouse_purchase[$key][0]->tax), gen_setting()->decimal, '.', '')}} - {{__('db.Net Sale Return')}} {{number_format((float)($warehouse_return[$key][0]->grand_total - $warehouse_return[$key][0]->tax), gen_setting()->decimal, '.', '')}} + {{__('db.Net Purchase Return')}} {{number_format((float)($warehouse_purchase_return[$key][0]->grand_total - $warehouse_purchase_return[$key][0]->tax), gen_setting()->decimal, '.', '')}}
                        </p>
                        <hr style="border-color: rgba(0, 0, 0, 0.2);">
                        <h4 class="text-center">{{number_format((float)$warehouse_expense[$key], gen_setting()->decimal, '.', '')}}</h4>
                        <p class="text-center">{{__('db.Expense')}}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div> -->
</div>