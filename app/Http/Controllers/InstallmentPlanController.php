<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\GiftCard;
use App\Models\PosSetting;
use Illuminate\Http\Request;
use App\Models\InstallmentPlan;
use App\Models\RewardPointSetting;
use App\Models\Sale;

class InstallmentPlanController extends Controller
{
    public function store(array $data) {
        $plan = InstallmentPlan::create($data);

        $months = $data['months'];
        $amount = $plan->total_amount - $plan->down_payment;
        if ($months > 0) {
            $amount = $amount / $months;
        }
        $startDate = now();

        for ($i = 1; $i <= $months; $i++) {
            $paymentDate = $startDate->copy()->addMonths($i);

            $plan->installments()->create([
                'status' => 'pending',
                'payment_date' => $paymentDate,
                'amount' => $amount,
            ]);
        }
    }

    public function show($id)
    {
        $plan = InstallmentPlan::with('installments')->findOrFail($id);

        // Check if the parent sale is already fully paid so we can disable the Add Payment button
        $parentSale = Sale::find($plan->reference_id);
        $saleFullyPaid = $parentSale && $parentSale->payment_status == 4;

        $lims_gift_card_list = GiftCard::where("is_active", true)->get();
        $lims_pos_setting_data = PosSetting::latest()->first();
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
        $lims_account_list = app(\App\Services\PaymentAccountService::class)->validOperationalAccounts();

        if($lims_pos_setting_data)
            $options = explode(',', $lims_pos_setting_data->payment_options);
        else
            $options = [];
        return view('backend.installment_plans.show', compact('plan', 'saleFullyPaid', 'lims_pos_setting_data', 'options', 'lims_reward_point_setting_data', 'lims_account_list', 'lims_gift_card_list'));
    }

    public function index()
    {
        $plans = InstallmentPlan::with('installments')->latest()->get();
        return view('backend.installment_plans.index', compact('plans'));
    }

    public function report(Request $request)
    {
        $status = $request->status;
        $starting_date = $request->starting_date ?? date('Y-m-d', strtotime('-1 month'));
        $ending_date = $request->ending_date ?? date('Y-m-d');

        $query = InstallmentPlan::with(['installments', 'reference']);

        if ($status == 'pending') {
            $query->whereHas('installments', function($q) {
                $q->where('status', 'pending');
            });
        } elseif ($status == 'overdue') {
            $query->whereHas('installments', function($q) {
                $q->where('status', 'pending')->whereDate('payment_date', '<', now());
            });
        }

        $plans = $query->latest()->get();
        
        return view('backend.report.installment_report', compact('plans', 'status', 'starting_date', 'ending_date'));
    }
}
