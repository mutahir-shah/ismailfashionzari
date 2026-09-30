<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use Auth;
use Keygen;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Traits\CacheForget;
use DB;

class CouponController extends Controller
{
    use CacheForget;
    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('unit')) {
            $lims_coupon_all = Coupon::where('is_active', true)->orderBy('id', 'desc')->get();
            return view('backend.coupon.index', compact('lims_coupon_all'));
        }
        else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }

    public function create()
    {
        //
    }

    public function generateCode()
    {
        $id = Keygen::alphanum(10)->generate();
        return $id;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['used'] = 0;
        $data['user_id'] = Auth::id();
        $data['is_active'] = true;
        Coupon::create($data);
        $this->cacheForget('coupon_list');
        return redirect('coupons')->with('message', __('db.Coupon created successfully'));
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        if($data['type'] == 'percentage')
            $data['minimum_amount'] = 0;
        $lims_coupon_data = Coupon::find($data['coupon_id']);
        $lims_coupon_data->update($data);
        $this->cacheForget('coupon_list');
        return redirect('coupons')->with('message', __('db.Coupon updated successfully'));
    }

    public function deleteBySelection(Request $request)
    {
        $coupon_id = $request['couponIdArray'];
        foreach ($coupon_id as $id) {
            $lims_coupon_data = Coupon::find($id);
            $lims_coupon_data->is_active = false;
            $lims_coupon_data->save();
        }
        $this->cacheForget('coupon_list');
        return 'Coupon deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_coupon_data = Coupon::find($id);
        $lims_coupon_data->is_active = false;
        $lims_coupon_data->save();
        $this->cacheForget('coupon_list');
        return redirect('coupons')->with('not_permitted', __('db.Coupon deleted successfully'));
    }
}
