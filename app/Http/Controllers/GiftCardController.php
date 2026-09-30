<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\GiftCard;
use App\Models\GiftCardRecharge;
use Illuminate\Support\Facades\Auth;
use Keygen;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class GiftCardController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('unit')) {
            $lims_customer_list = Customer::where('is_active', true)->get();
            $lims_user_list = User::where('is_active', true)->get();
            $lims_gift_card_all = GiftCard::where('is_active', true)->orderBy('id', 'desc')->get();

            return view('backend.gift_card.index', compact('lims_customer_list', 'lims_user_list', 'lims_gift_card_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function generateCode()
    {
        $id = Keygen::numeric(16)->generate();
        return $id;
    }

    public function store(Request $request)
    {
        try {
        \DB::beginTransaction();

        $this->validate($request, [
            'card_no' => [
                'max:255',
                    Rule::unique('gift_cards')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        $data = $request->all();

        if($request->input('user'))
            $data['customer_id'] = null;
        else
            $data['user_id'] = null;

        $data['is_active'] = true;
        $data['created_by'] = Auth::id();
        $data['expense'] = 0;
        $giftCard = GiftCard::create($data);

        // === ACCOUNTING ENGINE PHASE 2E: GIFT CARD SALE ===
        $accountingService = app(\App\Services\AccountingService::class);
        $result = $accountingService->recordGiftCardSale($giftCard, 'gift_card_created');
        if (!$result->success) {
            \Log::error('Accounting failed for GiftCard creation', ['gift_card_id' => $giftCard->id, 'error' => $result->error]);
            if (\Schema::hasColumn($giftCard->getTable(), 'accounting_status')) {
                $giftCard->accounting_status = 'failed';
                $giftCard->save();
            }
        }
        // ===========================================

        \DB::commit();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GiftCard creation failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'GiftCard creation failed: ' . $e->getMessage());
        }

        $message = 'GiftCard created successfully';
        if($data['user_id']){
            $lims_user_data = User::find($data['user_id']);
            $data['email'] = $lims_user_data->email;
            $data['name'] = $lims_user_data->name;
            try{
                Mail::send( 'mail.gift_card_create', $data, function( $message ) use ($data)
                {
                    $message->to( $data['email'] )->subject( 'GiftCard' );
                });
            }
            catch(\Exception $e){
                $message = 'GiftCard created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        else{
            $lims_customer_data = Customer::find($data['customer_id']);
            if($lims_customer_data->email){
                $data['email'] = $lims_customer_data->email;
                $data['name'] = $lims_customer_data->name;
                try{
                    Mail::send( 'mail.gift_card_create', $data, function( $message ) use ($data)
                    {
                        $message->to( $data['email'] )->subject( 'GiftCard' );
                    });
                }
                catch(\Exception $e){
                    $message = 'GiftCard created successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
                }
            }
        }
        return redirect('gift_cards')->with('message', $message);
    }

    public function edit($id)
    {
        $lims_gift_card_data = GiftCard::find($id);
        return $lims_gift_card_data;
    }

    public function update(Request $request, $id)
    {
        try {
        \DB::beginTransaction();

        $request['card_no'] = $request['card_no_edit'];
        $this->validate($request, [
            'card_no' => [
                'max:255',
                Rule::unique('gift_cards')->ignore($request['gift_card_id'])->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        $data = $request->all();
        $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
        $lims_gift_card_data->card_no = $data['card_no_edit'];
        $lims_gift_card_data->amount = $data['amount_edit'];
        if($request->input('user_edit')){
            $lims_gift_card_data->user_id = $data['user_id_edit'];
            $lims_gift_card_data->customer_id = null;
        }
        else{
            $lims_gift_card_data->user_id = null;
            $lims_gift_card_data->customer_id = $data['customer_id_edit'];
        }
        $lims_gift_card_data->expired_date = $data['expired_date_edit'];
        $lims_gift_card_data->save();

        \DB::commit();
        return redirect('gift_cards')->with('message', __('db.GiftCard updated successfully'));

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GiftCard update failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'GiftCard update failed: ' . $e->getMessage());
        }
    }

    public function recharge(Request $request, $id)
    {
        try {
        \DB::beginTransaction();

        $data = $request->all();
        $data['user_id'] = Auth::id();
        $lims_gift_card_data = GiftCard::find($data['gift_card_id']);
        if($lims_gift_card_data->customer_id)
            $lims_customer_data = Customer::find($lims_gift_card_data->customer_id);
        else
            $lims_customer_data = User::find($lims_gift_card_data->user_id);

        $lims_gift_card_data->amount += $data['amount'];
        $lims_gift_card_data->save();
        $recharge = GiftCardRecharge::create($data);

        // === ACCOUNTING ENGINE PHASE 2E: GIFT CARD RECHARGE ===
        $accountingService = app(\App\Services\AccountingService::class);
        $result = $accountingService->recordGiftCardSale($lims_gift_card_data, 'gift_card_recharged');
        if (!$result->success) {
            \Log::error('Accounting failed for GiftCard recharge', ['gift_card_id' => $lims_gift_card_data->id, 'error' => $result->error]);
            if (\Schema::hasColumn($lims_gift_card_data->getTable(), 'accounting_status')) {
                $lims_gift_card_data->accounting_status = 'failed';
                $lims_gift_card_data->save();
            }
        }
        // ===========================================

        \DB::commit();

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GiftCard recharge failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'GiftCard recharge failed: ' . $e->getMessage());
        }

        $message = 'GiftCard recharged successfully';
        if($lims_customer_data->email){
            $data['email'] = $lims_customer_data->email;
            $data['name'] = $lims_customer_data->name;
            $data['card_no'] = $lims_gift_card_data->card_no;
            $data['balance'] = $lims_gift_card_data->amount - $lims_gift_card_data->expense;
            try{
                Mail::send( 'mail.gift_card_recharge', $data, function( $message ) use ($data)
                {
                    $message->to( $data['email'] )->subject( 'GiftCard Recharge Info' );
                });
            }
            catch(\Exception $e){
                $message = 'GiftCard recharged successfully. Please setup your <a href="setting/mail_setting">mail setting</a> to send mail.';
            }
        }
        return redirect('gift_cards')->with('message', $message);
    }

    public function deleteBySelection(Request $request)
    {
        try {
            \DB::beginTransaction();
            $gift_card_id = $request['gift_cardIdArray'];
            $accountingService = app(\App\Services\AccountingService::class);
            foreach ($gift_card_id as $id) {
                $lims_gift_card_data = GiftCard::find($id);
                $lims_gift_card_data->is_active = false;
                $lims_gift_card_data->save();
                
                // === ACCOUNTING ENGINE PHASE 2E: DELETION ===
                $accountingService->reverseTransaction(get_class($lims_gift_card_data), $lims_gift_card_data->id, '_deleted');
            }
            \DB::commit();
            return 'Gift Card deleted successfully!';
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GiftCard bulk deletion failed: ' . $e->getMessage());
            return 'GiftCard bulk deletion failed: ' . $e->getMessage();
        }
    }

    public function destroy($id)
    {
        try {
            \DB::beginTransaction();
            $lims_gift_card_data = GiftCard::find($id);
            $lims_gift_card_data->is_active = false;
            $lims_gift_card_data->save();
            
            // === ACCOUNTING ENGINE PHASE 2E: DELETION ===
            $accountingService = app(\App\Services\AccountingService::class);
            $accountingService->reverseTransaction(get_class($lims_gift_card_data), $lims_gift_card_data->id, '_deleted');

            \DB::commit();
            return redirect('gift_cards')->with('not_permitted', __('db.Data deleted successfully'));
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('GiftCard deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('not_permitted', 'GiftCard deletion failed: ' . $e->getMessage());
        }
    }
}
