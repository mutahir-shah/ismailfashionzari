<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Returns;
use App\Models\CashRegister;
use App\Models\PosSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Domain\CashRegisterDomainService;

class CashRegisterController extends Controller
{
	public function index()
	{
		if(Auth::user()->role_id <= 2) {
			$lims_cash_register_all = CashRegister::with('user', 'warehouse')->orderBy('updated_at', 'desc')->get();
			return view('backend.cash_register.index', compact('lims_cash_register_all'));
		}
		else
			return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
	}
	public function store(Request $request)
	{
		app(CashRegisterDomainService::class)->open($request->all(), Auth::id());
		return redirect()->back()->with('message', __('db.Cash register created successfully'));
	}

	public function getDetails($id)
	{
		$fixed_methods = array_map('strtolower', [
			'cash', 'card', 'credit', 'cheque', 'gift_card',
			'deposit', 'points', 'razorpay', 'pesapal', 'installment'
		]);
		$current_methods = PosSetting::value('payment_options');
		$current_methods = array_map(
			fn ($method) => strtolower(trim($method)),
			explode(',', $current_methods)
		);
		$custom_methods = array_values(
			array_diff($current_methods, $fixed_methods)
		);

		$cash_register_data = CashRegister::findOrFail($id);
		$salePaymentQuery = function () use ($cash_register_data) {
			return Payment::where('cash_register_id', $cash_register_data->id)
				->whereNotNull('sale_id')
				->whereNull('return_id')
				->whereExists(function ($q) {
					$q->select(DB::raw(1))
						->from('sales')
						->whereColumn('sales.id', 'payments.sale_id')
						->whereNull('sales.deleted_at');
				});
		};
		$customerRefundQuery = function () use ($cash_register_data) {
			return Payment::where('cash_register_id', $cash_register_data->id)
				->whereNotNull('sale_id')
				->whereNotNull('return_id')
				->whereExists(function ($q) {
					$q->select(DB::raw(1))
						->from('sales')
						->whereColumn('sales.id', 'payments.sale_id')
						->whereNull('sales.deleted_at');
				});
		};
		$purchasePaymentQuery = function () use ($cash_register_data) {
			return Payment::where('cash_register_id', $cash_register_data->id)
				->whereNotNull('purchase_id')
				->whereNull('return_id')
				->whereNull('purchase_return_id')
				->whereExists(function ($q) {
					$q->select(DB::raw(1))
						->from('purchases')
						->whereColumn('purchases.id', 'payments.purchase_id')
						->whereNull('purchases.deleted_at');
				});
		};
		$supplierRefundQuery = function () use ($cash_register_data) {
			return Payment::where('cash_register_id', $cash_register_data->id)
				->whereNotNull('purchase_id')
				->where(function ($q) {
					$q->whereNotNull('return_id')
						->orWhereNotNull('purchase_return_id');
				})
				->whereExists(function ($q) {
					$q->select(DB::raw(1))
						->from('purchases')
						->whereColumn('purchases.id', 'payments.purchase_id')
						->whereNull('purchases.deleted_at');
				});
		};
		$cashInByMethod = function (string $method) use ($salePaymentQuery, $supplierRefundQuery) {
			$normalizedMethod = strtolower(trim($method));
			return $salePaymentQuery()->whereRaw('LOWER(paying_method) = ?', [$normalizedMethod])->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'))
				+ $supplierRefundQuery()->whereRaw('LOWER(paying_method) = ?', [$normalizedMethod])->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
		};

		$data['cash_in_hand'] = $cash_register_data->cash_in_hand;
		$data['actual_cash'] = $cash_register_data->actual_cash;
		$data['total_sale_amount'] = Sale::where([
										['cash_register_id', $cash_register_data->id],
										['sale_status', 1]
									])
									->whereNull('deleted_at')
									->where(function ($q) {
                                        $q->where('sale_type', '!=', 'opening balance')
                                        ->orWhereNull('sale_type');
                                    })
									->sum(DB::raw('grand_total / exchange_rate'));
		$data['total_payment'] = $salePaymentQuery()->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'))
								+ $supplierRefundQuery()->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
		$data['cash_payment'] = $cashInByMethod('Cash');
		$data['credit_card_payment'] = $cashInByMethod('Credit Card');
		$data['gift_card_payment'] = $cashInByMethod('Gift Card');
		$data['deposit_payment'] = $cashInByMethod('Deposit');
		$data['cheque_payment'] = $cashInByMethod('Cheque');
		$data['paypal_payment'] = $cashInByMethod('Paypal');
		$data['total_supplier_payment'] = $purchasePaymentQuery()->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
		$data['total_sale_return'] = $customerRefundQuery()->sum(DB::raw('amount / COALESCE(NULLIF(exchange_rate, 0), 1)'));
		$data['total_expense'] = Expense::where('cash_register_id', $cash_register_data->id)->sum('amount');
		$data['total_cash'] = $data['cash_in_hand'] + $data['total_payment'] - ($data['total_sale_return'] + $data['total_expense'] + $data['total_supplier_payment']);
		$data['status'] = $cash_register_data->status;

		foreach ($custom_methods as $method) {
			$key = $method . '_payment';
			$data['custom_methods'][$key] = $cashInByMethod($method);
		}
		
		return $data;
	}

	public function close(Request $request)
	{
		$cash_register_data = CashRegister::findOrFail($request->cash_register_id);
		app(CashRegisterDomainService::class)->close(
			$cash_register_data,
			(float) $request->closing_balance,
			(float) $request->actual_cash
		);
		return redirect()->back()->with('message', __('db.Cash register closed successfully'));
	}

    public function checkAvailability($warehouse_id)
    {
        $open_register_number = CashRegister::select('id')->where([
            ['user_id', Auth::id()],
            ['warehouse_id', $warehouse_id],
            ['status', true],
        ])->first();

        if ($open_register_number) {
            return response()->json($open_register_number->id);
        }

        return response()->json(false);
    }
}
