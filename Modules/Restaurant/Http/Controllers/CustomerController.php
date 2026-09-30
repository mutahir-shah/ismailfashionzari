<?php

namespace Modules\Restaurant\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithPaypal;
use App\Models\Product_Sale;
use Auth;
use Session;
use DB;

class CustomerController extends Controller
{
    public function index()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        return view('restaurant::frontend.dashboard', compact('customer'));
    }

	public function orders()
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if($customer){
            $sales = Sale::select('id','reference_no','grand_total','shipping_cost','coupon_discount','sale_status','created_at')->where('customer_id',$customer->id)->orderBy('created_at','DESC')->get();
            
            return view('restaurant::frontend.orders', compact('customer', 'sales'));
        }
        
        return view('restaurant::frontend.orders');
    }
    
    public function orderDetails($id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $sale = Sale::select('id','reference_no','grand_total','shipping_cost','coupon_discount','sale_status','created_at')->where('id',$id)->where('customer_id',$customer->id)->first();
        $products = Product_Sale::select('products.name', 'product_sales.qty', 'product_sales.net_unit_price')->join('products','products.id','=','product_sales.product_id')->where('Sale_id', $id)->get();
        return view('restaurant::frontend.order-details', compact('products', 'sale'));
    }
    
    public function orderCancel($id)
    {
        $sale = Sale::find($id);
        $sale->sale_status = 3;
        $sale->save();
        
        return redirect()->back()->with('message', 'You have canceled your order.');
    }

    public function wishlist()
    {
        $customer = Customer::select('id','wishlist','user_id')->where('user_id', Auth::id())->first();
        if(isset($customer->wishlist)){
            $products = DB::table('products')->whereIn('id',explode(',', $customer->wishlist))->get();
            return view('restaurant::frontend.wishlist', compact('products'));
        }
        return view('restaurant::frontend.wishlist');
    }

    public function addToWishlist($product_id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        if(isset($customer->wishlist)){
            if(in_array($product_id,explode(',',$customer->wishlist))){
                return response()->json('fail');
            }
            $customer->wishlist = $customer->wishlist.$product_id.',';
        } else {
            $customer->wishlist = $product_id.',';
        }
        $customer->save();
        
        return response()->json('success');
    }

    public function deleteFromWishlist($product_id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $customer->wishlist = str_replace($product_id.',', '', $customer->wishlist);
        $customer->save();
        
        return response()->json('success');
    }

    public function address()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $addresses = DB::table('customer_addresses')->where('customer_id',$customer->id)->get();
        return view('restaurant::frontend.addresses', compact('customer','addresses'));
    }

    public function addressCreate(Request $request)
    {
        $data = [
            'name'              => trim(htmlspecialchars($request->input('name'))),
            'phone'             => trim(htmlspecialchars($request->input('phone'))),
            'address'           => trim(htmlspecialchars($request->input('address'))),
            'state'             => trim(htmlspecialchars($request->input('state'))),
            'city'              => trim(htmlspecialchars($request->input('city'))),
            'country'           => trim(htmlspecialchars($request->input('country'))),
            'zip'               => trim(htmlspecialchars($request->input('zip'))),
            'customer_id'       => $request->input('customer_id'),
        ];

        DB::table('customer_addresses')->insert($data);

        Session::flash('message', 'Address inserted');
        Session::flash('type', 'success'); 

        return redirect()->back();

    }

    public function addressDefault($id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $addresses = DB::table('customer_addresses')->where('customer_id',$customer->id)->update(['default' => 0]);
        DB::table('customer_addresses')->where('id',$id)->update(['default' => 1]);

        $default = DB::table('customer_addresses')->where('id',$id)->first();

        $customer->address = $default->address;
        $customer->city = $default->city;
        $customer->state = $default->state;
        $customer->country = $default->country;
        $customer->postal_code = $default->zip;
        $customer->save();

        return redirect()->back();
    } 

    public function addressEdit($id)
    {
        $address = DB::table('customer_addresses')->where('id',$id)->first();
        return $address;
    }

    public function addressUpdate(Request $request)
    {
        $data = $request->except('_token');
        DB::table('customer_addresses')->where('id',$request->id)->update($data);
        $default = DB::table('customer_addresses')->where('id',$request->id)->where('default',1)->first();
        if(isset($default)){
            $customer = Customer::where('user_id', Auth::id())->first();
            $customer->address = $default->address;
            $customer->city = $default->city;
            $customer->state = $default->state;
            $customer->country = $default->country;
            $customer->postal_code = $default->zip;
            $customer->save();
        }

        Session::flash('message', 'Address updated');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function addressDelete($id)
    {
        DB::table('customer_addresses')->where('id',$id)->delete();

        Session::flash('message', 'Address deleted');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function accountDetails()
    {
        $customer = Customer::select('id')->where('user_id', Auth::id())->first();
        return view('restaurant::frontend.account-details', compact('customer'));
    }
    
    public function updateAccountDetails(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required',
            'email'    => 'required'
        ]);
        
        $id = auth()->user()->id;
        
        $user = User::find($id);
        $user->name = trim(htmlspecialchars($request->input('name')));
        $user->email = trim(htmlspecialchars($request->input('email')));
        $user->phone = trim(htmlspecialchars($request->input('phone')));
        $user->save();

        $customer = Customer::where('user_id',$id)->first();
        $customer->name = $user->name;
        $customer->email = $user->email;
        $customer->phone_number = $user->phone;
        $customer->save();
            
        Session::flash('message', 'Details updated');
        Session::flash('type', 'success'); 

        return redirect()->back();
    }
}
