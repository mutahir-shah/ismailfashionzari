<?php

namespace App\Http\Controllers;

use Mail;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\MailSetting;
use App\Mail\CustomerCreate;
use App\Mail\SupplierCreate;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use App\Models\PurchaseProductReturn;
use App\Models\ReturnPurchase;
use App\Models\PaymentWithCheque;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class SupplierController extends Controller
{
    use \App\Traits\MailInfo;

    public function __construct(public \App\Services\AccountingService $accountingService)
    {
    }

    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('suppliers-index')) {
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $lims_supplier_all = Supplier::where('is_active', true)->get();
            $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();
            return view('backend.supplier.index', compact('lims_supplier_all', 'all_permission', 'lims_account_list'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function clearDue(Request $request)
    {
        $request->validate([
            'supplier_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => ['nullable', 'integer'],
            'paid_by_id' => ['nullable', 'in:Cash,Bank,Cheque'],
            'cheque_no' => ['nullable', 'required_if:paid_by_id,Cheque'],
        ]);

        $lims_due_purchase_data = Purchase::select('id', 'warehouse_id', 'grand_total', 'paid_amount', 'payment_status')
            ->whereNull('deleted_at')
            ->where([
                ['payment_status', 1],
                ['supplier_id', $request->supplier_id]
            ])->get();
        $total_paid_amount = $request->amount;
        foreach ($lims_due_purchase_data as $key => $purchase_data) {
            if ($total_paid_amount == 0)
                break;
            $returned_amount = $this->actualPurchaseReturnTotalForPurchase($purchase_data->id);
            $due_amount = max(0, $purchase_data->grand_total - $returned_amount - $purchase_data->paid_amount);
            if ($due_amount <= 0)
                continue;

            if ($request->cash_register)
                $cash_register_id = $request->cash_register;
            else
                $cash_register_id = null;
            $account_id = $request->account_id ?: Account::select('id')->where('is_default', 1)->value('id');
            $paying_method = $request->paid_by_id ?: 'Cash';
            if ($total_paid_amount >= $due_amount) {
                $paid_amount = $due_amount;
                $payment_status = 2;
            } else {
                $paid_amount = $total_paid_amount;
                $payment_status = 1;
            }
            $payment = Payment::create([
                'payment_reference' => 'ppr-' . date("Ymd") . '-' . date("his"),
                'purchase_id' => $purchase_data->id,
                'user_id' => Auth::id(),
                'cash_register_id' => $cash_register_id,
                'account_id' => $account_id,
                'amount' => $paid_amount,
                'change' => 0,
                'paying_method' => $paying_method,
                'payment_note' => $request->note
            ]);

            if ($paying_method === 'Cheque') {
                PaymentWithCheque::create([
                    'payment_id' => $payment->id,
                    'cheque_no' => $request->cheque_no,
                ]);
            }

            $result = $this->accountingService->recordPayment($payment);
            if (!$result->success) {
                \Log::error('Accounting failed for supplier due payment', [
                    'payment_id' => $payment->id,
                    'error' => $result->error,
                ]);
            }

            $purchase_data->paid_amount += $paid_amount;
            $purchase_data->payment_status = $payment_status;
            $purchase_data->save();
            $total_paid_amount -= $paid_amount;
        }
        return redirect()->back()->with('message', __('db.Due cleared successfully'));
    }

    public function create()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('suppliers-add')) {
            $lims_customer_group_all = CustomerGroup::where('is_active', true)->get();
            return view('backend.supplier.create', compact('lims_customer_group_all'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'company_name' => [
                'max:255',
                Rule::unique('suppliers')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'email' => [
                'max:255',
                Rule::unique('suppliers')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        //validation for customer if create both user and supplier
        if (isset($request->both)) {
            $this->validate($request, [
                'phone_number' => [
                    'max:255',
                    Rule::unique('customers')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
            ]);
        }

        try {
        DB::beginTransaction();

        $lims_supplier_data = $request->except('image');
        $lims_supplier_data['is_active'] = true;
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['company_name']);
            $imageName = $imageName . '.' . $ext;
            $image->move(public_path('images/supplier'), $imageName);
            $lims_supplier_data['image'] = $imageName;
        }
        $create_supplier = Supplier::create($lims_supplier_data);

        // create dummy purchase if supplier has opening balance (due)
        if (isset($lims_supplier_data['opening_balance']) && $lims_supplier_data['opening_balance'] > 0) {
            $lims_purchase_data = new Purchase();
            $lims_purchase_data->reference_no = 'sob-' . date("Ymd") . '-' . date("his"); 
            $lims_purchase_data->supplier_id = $create_supplier->id;
            $lims_purchase_data->user_id = Auth::id();
            $lims_purchase_data->warehouse_id = 1;
            $lims_purchase_data->item = 0;
            $lims_purchase_data->total_qty = 0;
            $lims_purchase_data->total_discount = 0;
            $lims_purchase_data->total_tax = 0;
            $lims_purchase_data->total_cost = $lims_supplier_data['opening_balance'];
            $lims_purchase_data->grand_total = $lims_supplier_data['opening_balance'];
            $lims_purchase_data->status = 1; // completed
            $lims_purchase_data->payment_status = 1; // pending
            $lims_purchase_data->paid_amount = 0;
            $lims_purchase_data->purchase_type = 'Opening balance';
            $lims_purchase_data->save();

            $this->accountingService->recordSupplierOpeningBalance($create_supplier);
        }

        $message = 'Supplier';
        if (isset($request->both)) {
            Customer::create($lims_supplier_data);
            $message .= ' and Customer';
        }

        DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Supplier creation failed: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('not_permitted', 'Supplier creation failed: ' . $e->getMessage());
        }

        $mail_setting = MailSetting::latest()->first();
        if ($lims_supplier_data['email'] && $mail_setting) {
            $this->setMailInfo($mail_setting);
            try {
                Mail::to($lims_supplier_data['email'])->send(new SupplierCreate($lims_supplier_data));
                if (isset($request->both))
                    Mail::to($lims_supplier_data['email'])->send(new CustomerCreate($lims_supplier_data));
                $message .= ' created successfully!';
            } catch (\Exception $e) {
                $message .= ' created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }

        // if ajax - from create purchase page
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id'      => $create_supplier->id,
                'name'    => $create_supplier->name, 
                'company_name' => $create_supplier->company_name,
                'pay_term_no' => $create_supplier->pay_term_no, 
                'pay_term_period' => $create_supplier->pay_term_period,
                'type'    => 'supplier'
            ]);
        }

        return redirect('supplier')->with('message', $message);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        $opening_balance = $supplier->opening_balance ?? 0;
        $total_purchases = 0;
        $total_returns = 0;
        $total_paid = 0;

        $total_purchase_amount = Purchase::select('id', 'grand_total')
            ->where('supplier_id', $supplier->id)
            ->where(function ($q) {
                $q->where('purchase_type', '!=', 'opening balance')
                    ->orWhereNull('purchase_type');
            })
            ->whereNull('deleted_at')
            ->sum('grand_total');

        if ($total_purchase_amount == 0) {
            $total_paid = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
                ->where('purchases.supplier_id', $supplier->id)
                ->whereNull('payments.return_id')
                ->whereNull('payments.purchase_return_id')
                ->whereNull('purchases.deleted_at')
                ->sum('payments.amount');
            $balance_due = $opening_balance - $total_paid;
        } else {

            $total_paid = Payment::join('purchases', 'purchases.id', '=', 'payments.purchase_id')
                ->where('purchases.supplier_id', $supplier->id)
                ->whereNull('payments.return_id')
                ->whereNull('payments.purchase_return_id')
                ->whereNull('purchases.deleted_at')
                ->sum('payments.amount');

            $total_returns = $this->actualPurchaseReturnTotalForSupplier($supplier->id);

            $balance_due = $opening_balance + $total_purchase_amount - $total_returns - $total_paid;
        }

        $net_purchase = $total_purchase_amount - $total_returns;

        return view('backend.supplier.view', [
            'lims_supplier_data' => $supplier,
            'opening_balance' => $opening_balance,
            'total_purchase' => $total_purchase_amount,
            'net_purchase' => $net_purchase,
            'total_paid' => $total_paid,
            'total_returns' => $total_returns,
            'balance_due' => $balance_due,
        ]);
    }

    public function ledger($id)
    {
        // Supplier Purchases
        $purchases = Purchase::where('supplier_id', $id)->whereNull('deleted_at')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'date'      => $p->date ?? $p->created_at->format('Y-m-d'),
                'type'      => $p->purchase_type ?? 'Purchase',
                'reference' => $p->reference_no,
                'debit'     => floatval($p->grand_total), // increase payable
                'credit'    => 0,
            ];
        });

        // Supplier Payments (must check correct column)
        // $payments = [];
        // foreach ($purchases as $purchase) {
        //     $purchasePayments = Payment::where('purchase_id', $purchase['id'])->get()->map(function ($p) {
        //         return [
        //             'id'        => $p->id,
        //             'date'      => $p->date ?? $p->created_at->format('Y-m-d'),
        //             'type'      => 'Payment',
        //             'reference' => $p->payment_reference ?? '-',
        //             'debit'     => 0,
        //             'credit'    => floatval($p->amount),
        //         ];
        //     })->toArray(); // convert collection to array

        //     $payments = array_merge($payments, $purchasePayments);
        // }
        $payments = Payment::whereIn('purchase_id', $purchases->pluck('id'))
                    ->whereNull('return_id')
                    ->whereNull('purchase_return_id')
                    ->get()
                    ->map(function ($p) {
                        return [
                            'id'        => $p->id,
                            'date'      => $p->date ?? $p->created_at,
                            'type'      => 'Payment',
                            'reference' => $p->payment_reference ?? '-',
                            'debit'     => 0,
                            'credit'    => floatval($p->amount),
                        ];
                    });

        // Supplier Returns (no supplier_id directly → join through purchase)
        $returns = ReturnPurchase::with('refundPayments')->where('supplier_id', $id)->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'date' => $r->created_at,
                'type' => 'Purchase Return',
                'reference' => $r->reference_no,
                'debit' => 0,
                'credit' => floatval($r->display_grand_total),
            ];
        });

        $seq = 1;

        $purchases = $purchases->map(function ($row) use (&$seq) {
            $row['sequence'] = $seq++;
            return $row;
        });

        $payments = $payments->map(function ($row) use (&$seq) {
            $row['sequence'] = $seq++;
            return $row;
        });

        $returns = $returns->map(function ($row) use (&$seq) {
            $row['sequence'] = $seq++;
            return $row;
        });

        // Merge All
        $ledger = $purchases->merge($payments)->merge($returns)->sortBy('sequence')->values();

        // Running Balance
        $balance = 0;

        $ledger = $ledger->map(function ($row) use (&$balance) {
            $balance += ($row['debit'] - $row['credit']);
            $row['balance'] = round($balance, 2);
            return $row;
        });

        $ledger = $ledger->reverse()->values();

        return response()->json(['data' => $ledger]);
    }

    public function edit($id)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('suppliers-edit')) {
            $lims_supplier_data = Supplier::where('id', $id)->first();
            return view('backend.supplier.edit', compact('lims_supplier_data'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'company_name' => [
                'max:255',
                Rule::unique('suppliers')->ignore($id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],

            'email' => [
                'max:255',
                Rule::unique('suppliers')->ignore($id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $lims_supplier_data = Supplier::findOrFail($id);

        try {
        DB::beginTransaction();

        $input = $request->except('image');
        $image = $request->image;
        if ($image) {
            $this->fileDelete(public_path('images/supplier/'), $lims_supplier_data->image);

            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = preg_replace('/[^a-zA-Z0-9]/', '', $request['company_name']);
            $imageName = $imageName . '.' . $ext;
            $image->move(public_path('images/supplier'), $imageName);
            $input['image'] = $imageName;
        }

        $old_opening_balance = $lims_supplier_data->opening_balance;
        
        $lims_supplier_data->update($input);

        if ($old_opening_balance != $lims_supplier_data->opening_balance) {
            $this->accountingService->reverseTransaction(get_class($lims_supplier_data), $lims_supplier_data->id);
            if ($lims_supplier_data->opening_balance > 0) {
                $this->accountingService->recordSupplierOpeningBalance($lims_supplier_data, 'supplier_opening_balance_updated');
            }
        }

        DB::commit();
        return redirect('supplier')->with('message', __('db.Data updated successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Supplier update failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Supplier update failed: ' . $e->getMessage());
        }
    }

    public function deleteBySelection(Request $request)
    {
        $supplier_id = $request['supplierIdArray'];
        foreach ($supplier_id as $id) {
            $lims_supplier_data = Supplier::findOrFail($id);
            $lims_supplier_data->is_active = false;
            $lims_supplier_data->save();
            $this->fileDelete(public_path('images/supplier/'), $lims_supplier_data->image);
        }
        return 'Supplier deleted successfully!';
    }

    public function destroy($id)
    {
        try {
        DB::beginTransaction();

        $lims_supplier_data = Supplier::findOrFail($id);
        $lims_supplier_data->is_active = false;
        $lims_supplier_data->save();
        $this->fileDelete(public_path('images/supplier/'), $lims_supplier_data->image);

        DB::commit();
        return redirect('supplier')->with('not_permitted', __('db.Data deleted successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Supplier deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'Supplier deletion failed: ' . $e->getMessage());
        }
    }

    public function importSupplier(Request $request)
    {
        $upload = $request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if ($ext != 'csv')
            return redirect()->back()->with('not_permitted', __('db.Please upload a CSV file'));
        $filename =  $upload->getClientOriginalName();
        $filePath = $upload->getRealPath();
        //open and read
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);
        $escapedHeader = [];
        //validate
        foreach ($header as $key => $value) {
            $lheader = strtolower($value);
            $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through othe columns
        while ($columns = fgetcsv($file)) {
            if ($columns[0] == "")
                continue;
            foreach ($columns as $key => $value) {
                $value = preg_replace('/\D/', '', $value);
            }
            $data = array_combine($escapedHeader, $columns);

            $supplier = Supplier::firstOrNew(['company_name' => $data['companyname']]);
            $supplier->name = $data['name'];
            $supplier->image = $data['image'];
            $supplier->vat_number = $data['vatnumber'];
            $supplier->email = $data['email'];
            $supplier->phone_number = $data['phonenumber'];
            $supplier->address = $data['address'];
            $supplier->city = $data['city'];
            $supplier->state = $data['state'];
            $supplier->postal_code = $data['postalcode'];
            $supplier->country = $data['country'];
            $supplier->is_active = true;
            $supplier->save();
            $message = 'Supplier Imported Successfully';

            $mail_setting = MailSetting::latest()->first();


            if ($data['email'] && $mail_setting) {
                try {
                    Mail::to($data['email'])->send(new SupplierCreate($data));
                } catch (\Excetion $e) {
                    $message = 'Supplier imported successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
                }
            }
        }
        return redirect('supplier')->with('message', $message);
    }

    public function suppliersAll()
    {
        $lims_supplier_list = DB::table('suppliers')->where('is_active', true)->get();

        $html = '';
        foreach ($lims_supplier_list as $supplier) {
            $html .= '<option value="' . $supplier->id . '">' . $supplier->name . ' (' . $supplier->phone_number . ')' . '</option>';
        }

        return response()->json($html);
    }

    public function supplierDue($id)
    {
        $lims_due_purchase_data = Purchase::where([
            ['payment_status', 1],
            ['supplier_id', $id]
        ])
            ->whereNull('deleted_at')
            ->get();
        $due = 0;
        foreach ($lims_due_purchase_data as $key => $purchase_data) {
            $due += ($purchase_data->grand_total - $purchase_data->paid_amount);
        }

        $returned_amount = DB::table('purchases')
            ->join('return_purchases', 'purchases.id', '=', 'return_purchases.purchase_id')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where([
                ['purchases.supplier_id', $id],
                ['purchases.payment_status', 1]
            ])
            ->whereNull('purchases.deleted_at');
        app(\App\Services\WarehouseAccessService::class)->scope($returned_amount, 'purchases.warehouse_id');
        $returned_amount = $returned_amount->sum(DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total)'));
        $due -= $returned_amount;

        return response()->json([$due]);
    }

    public function supplierPayments($supplier_id)
    {
        $payments = DB::table('payments')
            ->join('purchases', 'payments.purchase_id', '=', 'purchases.id')
            ->where('purchases.supplier_id', $supplier_id)
            ->whereNull('payments.return_id')
            ->whereNull('payments.purchase_return_id')
            ->whereNull('purchases.deleted_at')
            ->select(
                'payments.id',
                'payments.created_at',
                'payments.payment_reference',
                'payments.amount',
                'payments.paying_method',
                'payments.payment_at'
            )
            ->latest('payments.created_at');
        app(\App\Services\WarehouseAccessService::class)->scope($payments, 'purchases.warehouse_id');
        $payments = $payments->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'created_at' => $payment->created_at ? date('Y-m-d', strtotime($payment->created_at)) : '-',
                    'payment_reference' => $payment->payment_reference ?? '-',
                    'amount' => number_format($payment->amount, 2),
                    'paying_method' => ucfirst($payment->paying_method ?? '-'),
                    'payment_at' => $payment->payment_at ? date('Y-m-d H:i', strtotime($payment->payment_at)) : date('Y-m-d H:i', strtotime($payment->created_at)),
                ];
            });

        return response()->json(['data' => $payments]);
    }

    public function supplierReturns($id)
    {
        $returns = ReturnPurchase::leftJoin('purchases', 'return_purchases.purchase_id', '=', 'purchases.id')
            ->leftJoin('warehouses', 'return_purchases.warehouse_id', '=', 'warehouses.id')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where('return_purchases.supplier_id', $id)
            ->select(
                'return_purchases.id',
                'return_purchases.created_at',
                'return_purchases.reference_no',
                'return_purchases.item',
                'return_purchases.total_qty',
                DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total) as grand_total'),
                'return_purchases.return_note',
                'return_purchases.staff_note',
                'purchases.reference_no as purchase_reference',
                'warehouses.name as warehouse_name'
            )
            ->latest('return_purchases.created_at')
            ->get()
            ->map(function ($return) {
                return [
                    'id' => $return->id,
                    'date' => $return->created_at ? date(config('date_format'), strtotime($return->created_at)) : '-',
                    'reference' => $return->reference_no ?? '-',
                    'purchase_reference' => $return->purchase_reference ?? '-',
                    'warehouse' => $return->warehouse_name ?? '-',
                    'item' => number_format($return->item ?? 0, 0),
                    'total_qty' => number_format($return->total_qty ?? 0, cache()->get('general_setting')->decimal),
                    'grand_total' => number_format($return->grand_total ?? 0, cache()->get('general_setting')->decimal),
                    'return_note' => $return->return_note ?: '-',
                    'staff_note' => $return->staff_note ?: '-',
                ];
            });

        return response()->json(['data' => $returns]);
    }

    private function actualPurchaseReturnTotalForPurchase(int $purchaseId): float
    {
        return (float) DB::table('return_purchases')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where('return_purchases.purchase_id', $purchaseId)
            ->sum(DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total)'));
    }

    private function actualPurchaseReturnTotalForSupplier(int $supplierId): float
    {
        return (float) DB::table('return_purchases')
            ->leftJoin(DB::raw('(select purchase_return_id, sum(amount) as refunded_amount from payments where purchase_return_id is not null group by purchase_return_id) as purchase_return_refunds'), 'purchase_return_refunds.purchase_return_id', '=', 'return_purchases.id')
            ->where('return_purchases.supplier_id', $supplierId)
            ->sum(DB::raw('COALESCE(purchase_return_refunds.refunded_amount, return_purchases.grand_total)'));
    }
}
