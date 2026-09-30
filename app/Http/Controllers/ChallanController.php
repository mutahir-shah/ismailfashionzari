<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PackingSlip;
use App\Models\Challan;
use App\Models\Product_Sale;
use App\Models\Sale;
use App\Models\Courier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PackingSlipProduct;
use App\Models\Account;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\GiftCard;
use App\Models\PosSetting;
use App\Models\RewardPointSetting;
use App\Helpers\DateHelper;
use Auth;
use AdnSms\AdnSms;
use DB;

class ChallanController extends Controller
{
    public function index(Request $request)
    {
        if($request->input('status'))
            $status = $request->input('status');
        else
            $status = 0;
        if($request->input('courier_id'))
            $courier_id = $request->input('courier_id');
        else
            $courier_id = 'All Courier';
        $courier_list = Courier::where('is_active', true)->get();
        $lims_gift_card_list = GiftCard::where("is_active", true)->get();
        $lims_pos_setting_data = PosSetting::latest()->first();
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
        $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();
        if($lims_pos_setting_data)
            $options = explode(',', $lims_pos_setting_data->payment_options);
        else
            $options = [];

        return view('backend.challan.index', compact('courier_id', 'courier_list', 'status', 'options', 'lims_pos_setting_data', 'lims_reward_point_setting_data', 'lims_gift_card_list', 'lims_account_list'));
    }

    public function challanData(Request $request)
    {
        $columns = array(
            0 => 'id',
            1 => 'created_at',
            2 => 'reference_no',
        );

        $courier_id = $request->input('courier_id');
        $status = $request->input('status');

        if($courier_id == 'All Courier' && !$status) {
            $totalData = Challan::count();
        }
        elseif($courier_id == 'All Courier' && $status) {
            $totalData = Challan::where('status', $status)->count();
        }
        elseif($courier_id && $status) {
            $totalData = Challan::where([
                            ['courier_id', $courier_id],
                            ['status', $status]
                        ])->count();
        }
        elseif($courier_id) {
            $totalData = Challan::where([
                            ['courier_id', $courier_id]
                        ])->count();
        }
        elseif($status) {
            $totalData = Challan::where([
                            ['status', $status]
                        ])->count();
        }
        else {
            $totalData = Challan::count();
        }

        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = 'challans.'.($columns[$request->input('order.0.column')] ?? 'created_at');
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value'))) {
            if($courier_id == 'All Courier' && !$status) {
                $challans = Challan::with('courier')
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            }
            elseif($courier_id == 'All Courier' && $status) {
                $challans = Challan::with('courier')
                            ->where('status', $status)
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            }
            elseif($courier_id && $status) {
                $challans = Challan::with('courier')
                            ->where([
                                ['courier_id', $courier_id],
                                ['status', $status]
                            ])
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            }
            elseif($courier_id) {
                $challans = Challan::with('courier')
                            ->where([
                                ['courier_id', $courier_id],
                            ])
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            }
            elseif($status) {
                $challans = Challan::with('courier')
                            ->where([
                                ['status', $status],
                            ])
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();
            }
        }
        else {
            $search = $request->input('search.value');
            if(substr($search, 0, 3) == 'dc-' || substr($search, 0, 3) == 'Dc-' || substr($search, 0, 3) == 'DC-') {
                $search = substr($search, 3, strlen($search));
                $reference_no = $search;
            }
            elseif($search[0] != 'n' && $search[0] != 'N')
                $reference_no = 'N'.$search;
            else
                $reference_no = $search;
            $packing_slip_data = PackingSlip::select('packing_slips.id')
                            ->join('sales', 'packing_slips.sale_id', '=', 'sales.id')
                            ->whereNull('sales.deleted_at')
                            ->where('sales.reference_no', $reference_no)
                            ->first();
            if($packing_slip_data) {
                $challans = Challan::where('packing_slip_list', 'LIKE', "%{$packing_slip_data->id}%")->get();
                $totalFiltered = Challan::where('packing_slip_list', 'LIKE', "%{$packing_slip_data->id}%")->count();
            }
            else {
                $challans = Challan::select('challans.*')
                            ->join('couriers', 'challans.courier_id', '=', 'couriers.id')
                            ->with('courier')
                            ->whereDate('challans.created_at', '=' , date('Y-m-d', strtotime($search)))
                            ->orWhere('challans.status', 'LIKE', "%{$search}%")
                            ->orWhere('challans.reference_no', 'LIKE', "%{$search}%")
                            ->orWhere('couriers.name', 'LIKE', "%{$search}%")
                            ->offset($start)
                            ->limit($limit)
                            ->orderBy($order, $dir)
                            ->get();

                $totalFiltered = Challan::
                                join('couriers', 'challans.courier_id', '=', 'couriers.id')
                                ->whereDate('challans.created_at', '=' , date('Y-m-d', strtotime($search)))
                                ->orWhere('challans.status', 'LIKE', "%{$search}%")
                                ->orwhere('challans.reference_no', 'LIKE', "%{$search}%")
                                ->orWhere('couriers.name', 'LIKE', "%{$search}%")
                                ->count();
            }
        }

       $data = array();

        if(!empty($challans))
        {
            foreach ($challans as $key => $challan)
            {
                $packingSlipList = explode(",", $challan->packing_slip_list);
                $amountList = explode(",", $challan->amount_list);
                $cashList = explode(",", $challan->cash_list);
                $deliveryChargeList = explode(",", $challan->delivery_charge_list);

                $nestedData['id'] = $challan->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format').' h:i:s', strtotime($challan->created_at));
                $nestedData['reference'] = 'DC-' . $challan->reference_no ?? 'N/A';
                $nestedData['sale_reference'] = '';
                foreach($packingSlipList as $index => $packingSlipId) {
                    $packingSlip = PackingSlip::with('sale')->find($packingSlipId);
                    if($packingSlip) {
                        if($index)
                            $nestedData['sale_reference'] .= ', ';
                        $nestedData['sale_reference'] .= $packingSlip->sale->reference_no ?? 'N/A';
                    }
                }

                $nestedData['courier'] = $challan->courier->name.' ['.$challan->courier->phone_number.']';

                if($challan->status == 'Active')
                    $nestedData['status'] = '<div class="badge badge-success">'.$challan->status.'</div>';
                elseif($challan->status == 'Close')
                    $nestedData['status'] = '<div class="badge badge-danger">'.$challan->status.'</div>';

                // if($challan->deposit_status == 'Deposited')
                //     $nestedData['deposit_status'] = '<div class="badge badge-success">'.$challan->deposit_status.'</div>';
                // elseif($challan->deposit_status == 'Not Deposited')
                //     $nestedData['deposit_status'] = '<div class="badge badge-danger">'.$challan->deposit_status.'</div>';
                // else
                //     $nestedData['deposit_status'] = 'N/A';

                if($challan->closing_date)
                    $nestedData['closing_date'] = date("d/m/Y", strtotime($challan->closing_date));
                else
                    $nestedData['closing_date'] = 'N/A';

                $total_amount = array_sum($amountList);
                $total_due = 0;
                foreach($packingSlipList as $packingSlipId) {
                    $packingSlip = PackingSlip::with('sale')->find($packingSlipId);
                    if($packingSlip && $packingSlip->sale) {
                        $total_due += ($packingSlip->sale->grand_total - $packingSlip->sale->paid_amount);
                    }
                }

                $nestedData['total_amount'] = $total_amount;
                $nestedData['total_due'] = $total_due;

                if($challan->created_by_id)
                    $nestedData['created_by'] = $challan->createdBy->name;
                else
                    $nestedData['created_by'] = 'N/A';

                if($challan->closed_by_id)
                    $nestedData['closed_by'] = $challan->closedBy->name;
                else
                    $nestedData['closed_by'] = 'N/A';

                $nestedData['options'] = '<div class="d-flex"><a href="'.route('challan.genInvoice', $challan->id).'" class="btn btn-primary btn-sm" title="Print Challan" target="_blank"><i class="ti ti-printer"></i></a>&nbsp;';

                if($challan->status == 'Active') {
                    $nestedData['options'] .= '<a href="javascript:void(0)" class="btn btn-success btn-sm add-payment" data-id="'.$challan->id.'" data-due="'.$total_due.'" data-toggle="modal" data-target="#add-payment" title="Finalize Challan"><i class="ti ti-cash-banknote"></i></a>&nbsp;';
                }
                elseif($challan->status == 'Close') {
                    $nestedData['options'] .= '<a href="'.route('challan.moneyReciept', $challan->id).'" class="btn btn-success btn-sm" title="Print Money Reciept" target="_blank"><i class="ti ti-copy"></i></a>';
                }
                $nestedData['options'] .= '</div>';
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);
    }

    public function create(Request $request)
    {
        $packing_slip_all = [];
        if (!$request->packing_slip_id) {
            return redirect()->back()->with('message', __('db.Please select at least one packing slip'));
        }
        $request['packing_slip_id'] = rtrim($request['packing_slip_id'], ',');

        $packing_slip_id_list = explode(",", $request->packing_slip_id);
        foreach ($packing_slip_id_list as $key => $id) {
            $packing_slip_data = PackingSlip::with('sale')
                                ->where([
                                    ['status', 'Pending'],
                                    ['id', $id]
                                ])->first();
            if($packing_slip_data) {
                $packing_slip_all[] = $packing_slip_data;
            }
        }
        if (!count($packing_slip_all)) {
            return redirect()->back()->with('message', __('db.Please close previous challan before creating a new one'));
        }
        // return dd($packing_slip_all);
        $last_challan = Challan::latest()->first();
        if($last_challan)
            $new_reference = (int)$last_challan->reference_no + 1;
        else
            $new_reference = 1001;
        $courier_list = Courier::where('is_active', true)->get();
        return view('backend.challan.create', compact('new_reference', 'packing_slip_all', 'courier_list'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if (isset($data['created_at'])) {
            $data['created_at'] = normalize_to_sql_datetime($data['created_at']);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $last_challan = Challan::orderBy('id', 'desc')->first();
        if($last_challan)
            $data['reference_no'] = (int)$last_challan->reference_no + 1;
        else
            $data['reference_no'] = 1001;
        $data['status'] = 'Active';
        foreach ($data['packing_slip_list'] as $key => $packing_slip_id) {
            $packing_slip = PackingSlip::with('delivery')->select('id', 'status', 'delivery_id')->find($packing_slip_id);

            $packing_slip->delivery->status = 2;
            $packing_slip->delivery->courier_id = $data['courier_id'];
            $packing_slip->delivery->save();

            $packing_slip->status = 'In Transit';
            $packing_slip->save();

        }
        $data['packing_slip_list'] = implode(",", $data['packing_slip_list']);
        $data['amount_list'] = implode(",", $data['amount_list']);
        $data['created_by_id'] = Auth::id();
        Challan::create($data);
        return redirect()->route('challan.index')->with('message', __('db.Challan created successfully'));
    }

    public function genInvoice($id)
    {
       $challan          = Challan::with('courier')->find($id);
        $general_setting = cache()->get('general_setting');
        $invoice_settings = \App\Models\InvoiceSetting::latest()->first();

        return view('backend.challan.invoice', compact(
            'challan',
            'general_setting',
            'invoice_settings'
        ));
    }

    public function finalize($id)
    {
        $challan = Challan::find($id);
        return view('backend.challan.finalize', compact('challan'));
    }

    private function findSumFromArray() {

    }

    public function update(Request $request, $id)
    {
        $data = $request->except('amount');
        $total_price = $request->amount;
        DB::beginTransaction();
        try {
            $challan = Challan::find($id);
            foreach ($data['paid_amount_list'] as $key => $amount) {
                if(!$amount)
                    $data['status_list'][$key] = 'Failed';
                else
                    $data['status_list'][$key] = 'Delivered';
            }

            $packing_slip_list = explode(",", $challan->packing_slip_list);
            $amount_list = explode(",", $challan->amount_list);
            $unpaid_total = 0;
            foreach ($packing_slip_list as $key => $ps_id) {
                $ps = PackingSlip::with('sale')->find($ps_id);
                if($ps->sale->payment_status != 4) {
                    $unpaid_total += $amount_list[$key];
                }
            }

            $input_amount = array_sum($data['paid_amount_list']);
            if (number_format($input_amount, 2, '.', '') != number_format($unpaid_total, 2, '.', '')) {
                DB::rollBack();
                return redirect()->route('challan.finalize', $id)->with('message', "Input amount ($input_amount) is not equal to unpaid total ($unpaid_total)");
            }

            $statusList = $data['status_list'];
            $cashList = $data['cash_list'];
            $chequeList = $data['cheque_list'];
            $onlinePaymentList = $data['online_payment_list'];

            $data['cash_list'] = implode(",", $data['cash_list']);
            $data['cheque_list'] = implode(",", $data['cheque_list']);
            $data['online_payment_list'] = implode(",", $data['online_payment_list']);
            $data['delivery_charge_list'] = implode(",", $data['delivery_charge_list']);
            $data['status_list'] = implode(",", $data['status_list']);
            $data['status'] = 'Close';
            $packing_slip_list = explode(",", $challan->packing_slip_list);
            $data['closing_date'] = date("Y-m-d");
            $data['closed_by_id'] = Auth::id();
            //$data['deposit_status'] = 'Not Deposited';
            $challan->update($data);

            foreach ($packing_slip_list as $key => $packing_slip_id) {
                $packing_slip = PackingSlip::with('sale', 'delivery', 'products')->find($packing_slip_id);

                if($statusList[$key] == 'Delivered') {
                    foreach ($packing_slip->products as $product) {
                        $product_sale = Product_Sale::where([
                                            ['sale_id', $packing_slip->sale_id],
                                            ['product_id', $product->id]
                                        ])->first();

                        //update product delivery status
                        $product_sale->is_delivered = true;
                        $product_sale->save();
                    }
                    $packing_slip->status = 'Delivered';
                    $packing_slip->save();
                    //update delivery status
                    $delivery = $packing_slip->delivery;
                    $packing_slip_ids = explode(",", $delivery->packing_slip_ids);
                    $packingSlipStatus = PackingSlip::whereIn("id", $packing_slip_ids)->pluck('status')->toArray();
                    if(!in_array('Pending', $packingSlipStatus)) {
                        $delivery->status = 3;
                        $delivery->save();
                    }
                }

                $paying_method = $data['paying_method_list'][$key] ?? 'Cash';
                $paid_amount = $data['paid_amount_list'][$key] ?? 0;
                $payment_note = $data['payment_note_list'][$key] ?? null;

                if($paid_amount > 0) {
                    $this->createPayment($paid_amount, $packing_slip->sale, $paying_method, $payment_note);
                }

                $delivered_product_number = Product_Sale::where([
                                                    ['sale_id', $packing_slip->sale_id],
                                                    ['is_delivered', true]
                                                ])->count();

                $non_delivered_product_number = Product_Sale::where([
                                                    ['sale_id', $packing_slip->sale_id],
                                                    ['is_delivered', false]
                                                ])->count();
                // if(isset($data['refund_list']) && in_array($packing_slip_id, $data['refund_list'])) {
                //     $packing_slip->status = 'Cancelled';
                //     $packing_slip->sale->status = 'Returned';
                //     $packing_slip->sale->return_note = $data['return_note'][$key];
                //     $packing_slip->sale->return_date = date("Y-m-d");
                //     foreach ($packing_slip->products as $product) {
                //         $product_sale = Product_Sale::where([
                //                             ['sale_id', $packing_slip->sale_id],
                //                             ['product_id', $product->id]
                //                         ])->first();
                //         if($product->type == 'combo') {
                //             $child_ids = explode(",", $product->child_list);
                //             $qty_list = explode(",", $product->qty_list);
                //             foreach ($child_ids as $index => $child_id) {
                //                 $child_product = Product::select('id', 'received_qty')->find($child_id);
                //                 $child_product->received_qty += $qty_list[$index] * $product_sale->qty;
                //                 $child_product->save();
                //             }
                //         }
                //         elseif($product->type == 'standard') {
                //             if($product_sale->package_id) {
                //                 $package_data = ProductSellingPackage::select('rate')->find($product_sale->package_id);
                //                 $product->received_qty += $package_data->rate * $product_sale->qty;
                //             }
                //             else
                //                 $product->received_qty += $product_sale->qty;
                //             $product->save();
                //         }
                //     }
                // }
                if($delivered_product_number && !$non_delivered_product_number)
                    $packing_slip->sale->sale_status = 1;
                // elseif($non_delivered_product_number) {
                //     $packing_slip->status = 'Pending';
                // }
                //checking the payment status
                if($packing_slip->sale->grand_total - $packing_slip->sale->paid_amount == 0)
                    $packing_slip->sale->payment_status = 4;

                $packing_slip->sale->save();
                $packing_slip->save();
            }
            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }
        return redirect()->route('challan.index')->with('message', __('db.Challan finalized successfully'));
    }

    public function createPayment($amount, $sale, $paying_method, $payment_note = null)
    {
        $lims_cash_register_data =  CashRegister::select('id')
                                        ->where([
                                            ['user_id', Auth::id()],
                                            ['warehouse_id', $sale->warehouse_id],
                                            ['status', 1]
                                        ])->first();
        if($lims_cash_register_data)
            $cash_register_id = $lims_cash_register_data->id;
        else
            $cash_register_id = null;
        $account_data = Account::select('id')->where('is_default', 1)->first();
        Payment::create([
            'payment_reference' => 'spr-'.date("Ymd").'-'.date("his"),
            'sale_id' => $sale->id,
            'user_id' => Auth::id(),
            'cash_register_id' => $cash_register_id,
            'account_id' => $account_data->id,
            'amount' => $amount,
            'change' => 0,
            'paying_method' => $paying_method,
            'payment_note' => $payment_note,
        ]);
        $sale->paid_amount += $amount;
        $sale->save();
    }

    public function addPayment(Request $request)
    {

        $data = $request->all();
        $challan = Challan::find($data['challan_id']);
        $packingSlipList = explode(",", $challan->packing_slip_list);
        $amount_list = explode(",", $challan->amount_list);

        $document = $request->document;
        if ($document) {
            $v = \Validator::make(
                [
                    'extension' => strtolower($request->document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $documentName = date("Ymdhis") . '.' . $ext;
            $document->move(public_path('documents/add-payment'), $documentName);
            $data['document'] = $documentName;
        }



        DB::beginTransaction();
        try {
            $delivery_charge_list = $data['delivery_charge_list'] ?? [];
            
            foreach ($packingSlipList as $key => $packing_slip_id) {
                $paying_method = $data['paying_method_list'][$key] ?? 'Cash';
                $paying_amount = $data['paid_amount_list'][$key] ?? 0;
                $payment_note = $data['payment_note_list'][$key] ?? null;

                if ($paying_amount > 0) {
                    $packing_slip = PackingSlip::with('sale')->find($packing_slip_id);
                    $sale = $packing_slip->sale;

                    if ($sale->payment_status == 4) continue; // Skip if already paid

                    $due = $sale->grand_total - $sale->paid_amount;
                    if ($paying_amount > $due) {
                        $paying_amount = $due;
                    }

                    $lims_cash_register_data = CashRegister::where([
                        ['user_id', Auth::id()],
                        ['warehouse_id', $sale->warehouse_id],
                        ['status', true]
                    ])->first();

                    $payment = new Payment();
                    $payment->user_id = Auth::id();
                    $payment->sale_id = $sale->id;
                    $payment->account_id = $data['account_id'];
                    if($lims_cash_register_data)
                        $payment->cash_register_id = $lims_cash_register_data->id;
                    $payment->payment_reference = 'spr-' . date("Ymd") . '-' . date("his");
                    $payment->amount = $paying_amount;
                    $payment->change = 0;
                    $payment->paying_method = $paying_method;
                    $payment->payment_note = $payment_note;
                    $payment->payment_receiver = $data['payment_receiver'];
                    if (isset($data['document'])) {
                        $payment->document = $data['document'];
                    }
                    $payment->payment_at = date('Y-m-d H:i:s');
                    $payment->save();

                    $sale->paid_amount += $paying_amount;
                    if ($sale->paid_amount >= $sale->grand_total) {
                        $sale->payment_status = 4;
                        $sale->sale_status = 1; // Completed
                        
                        // Mark products as delivered
                        $packing_slip_products = PackingSlipProduct::where('packing_slip_id', $packing_slip_id)->get();
                        foreach ($packing_slip_products as $ps_product) {
                            Product_Sale::where([
                                ['sale_id', $sale->id],
                                ['product_id', $ps_product->product_id]
                            ])->update(['is_delivered' => true]);
                        }
                        $packing_slip->status = 'Delivered';
                        $packing_slip->save();
                    }
                    else
                        $sale->payment_status = 3;
                    $sale->save();
                }
            }

            // Update Challan
            $all_paid = true;
            foreach ($packingSlipList as $ps_id) {
                $ps = PackingSlip::with('sale')->find($ps_id);
                if ($ps->sale->payment_status != 4) {
                    $all_paid = false;
                    break;
                }
            }
            if ($all_paid) {
                $challan->status = 'Close';
                $challan->closing_date = date("Y-m-d");
                $challan->closed_by_id = Auth::id();
                $challan->delivery_charge_list = implode(",", $delivery_charge_list);
                $challan->save();
            }

            DB::commit();
            return redirect()->back()->with('message', 'Payment added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('message', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function getPackingSlips($id)
    {
        $challan = Challan::find($id);
        $packing_slip_list = array_filter(explode(",", $challan->packing_slip_list));
        $amount_list = array_filter(explode(",", $challan->amount_list));
        
        $data = [];
        foreach ($packing_slip_list as $key => $ps_id) {
            $ps = PackingSlip::with('sale')->find($ps_id);
            if ($ps) {
                $data[] = [
                    'id' => $ps->id,
                    'reference' => 'P' . $ps->reference_no,
                    'order_reference' => $ps->sale->reference_no ?? 'N/A',
                    'amount' => $amount_list[$key],
                    'total_amount' => $ps->sale->grand_total,
                    'due' => $ps->sale->grand_total - $ps->sale->paid_amount,
                    'is_paid' => ($ps->sale->payment_status == 4)
                ];
            }
        }
        return response()->json($data);
    }

    public function moneyReciept($id)
    {
        $challan = Challan::find($id);
        return view('backend.challan.money_reciept', compact('challan'));
    }
}
