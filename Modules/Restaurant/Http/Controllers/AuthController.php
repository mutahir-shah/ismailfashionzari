<?php

namespace Modules\Restaurant\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Modules\Restaurant\Mail\Verify;
use Modules\Restaurant\Mail\VerifyCode;
use App\Models\MailSetting;
use Mail;
use Session;

class AuthController extends Controller
{
    use \App\Traits\MailInfo;

    public function register()
    {
        return view('restaurant::frontend.registration');
    }

    public function processRegisterCustomer(Request $request) 
    {
        $this->validate($request, [
            'email'       => 'required|unique:users,email',
            'password'    => 'required|min:6|confirmed'
        ]);

        try {

            $data =  [
                'name'        => trim(htmlspecialchars($request->input('name'))),
                'email'       => trim(htmlspecialchars($request->input('email'))),
                'password'    => trim(bcrypt($request->input('password'))),
                'phone'       => '',
                'role_id'     => 5,
                'is_active'   => 0,
                'is_deleted'  => 0
            ];

            $user = User::create($data);
            
            $id = $user->id;

            $data =  [
                'name'               => trim(htmlspecialchars($request->input('name'))),
                'email'              => trim(htmlspecialchars($request->input('email'))),
                'phone_number'       => '',
                'address'            => '',
                'city'               => '',
                'user_id'            => $id,
                'customer_group_id'  => 1,
                'is_active'          => 0,
            ];

            $customer = Customer::create($data);

            $mail_setting = MailSetting::latest()->first();
            $this->setMailInfo($mail_setting);
            Mail::to($request->input('email'))->send(new Verify($data));

            return redirect()->route('customer.login', ['verify' => 0]);

        } catch(Exception $e) {

            $this->setErrorMessage($e->getMessage());

            return redirect()->back();
        }

    }

    public function verify($id)
    {
        $user = User::find($id);
        $user->is_active = 1;
        $user->save();

        $customer = Customer::where('user_id',$id)->first();
        $customer->is_active = 1;
        $customer->save();

        return redirect()->route('customer.login', ['verify' => 1]);
    }

    public function login($verify='')
    {
        return view('restaurant::frontend.login', compact('verify'));
    }

    public function processLogin(Request $request) 
    {
        $this->validate($request, [
            'email'   => 'required',
            'password'=> 'required|min:6'

        ]);

        $credentials = $request->except(['_token','checkout']);

        if(auth()->attempt($credentials)) {
            
            $user = auth()->user();

            if(isset($request->checkout) && ($request->checkout == 1)){
                return redirect()->back();
            }
            
            return redirect()->route('customer.profile'); 

        } else {
            Session::flash('message', 'Invalid email or password');
            Session::flash('type', 'danger'); 
    
            return view('restaurant::frontend.login');
        }

    }

    public function getEmail()
    {
        return view('restaurant::frontend.forgot-password');
    }

    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $user = User::where('email',$email)->first();
        if(!empty($user)) {
            $mail_setting = MailSetting::latest()->first();
            $this->setMailInfo($mail_setting);

            $data = [
                'code' => rand(100000, 999999),
                'id'   => $user->id
            ];

            Mail::to($email)->send(new VerifyCode($data));

            session(['verification_code' => $data['code']]);

            return view('restaurant::frontend.verify-code',compact('user'));
        } else {
            Session::flash('message', 'This email is not in our system!');
            Session::flash('type', 'danger');
            
            return view('restaurant::frontend.forgot-password'); 
        }
    }
    
    public function getPass(Request $request)
    {
        $code = session()->has('verification_code') ? session()->get('verification_code') : 0;
        if($code = $request->code){
            $id = $request->id;
            return view('restaurant::frontend.change-password',compact('id'));
        } else {
            Session::flash('message', 'Sorry, verification code didn not match');
            Session::flash('type', 'danger');
            
            return view('restaurant::frontend.forgot-password'); 
        }
    }
    
    public function changePass(Request $request)
    {
        $password = $request->password;
        $id = $request->id;
        
        $user = User::find($id);
        $user->password = trim(bcrypt($password));
        $user->save();
        
        Session::flash('message', 'Your password has been updated.');
        Session::flash('type', 'danger');
        
        return view('restaurant::frontend.login'); 
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return redirect('/customer/login');
    }
}
