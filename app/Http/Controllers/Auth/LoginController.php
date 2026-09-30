<?php



namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Cache;
use DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class LoginController extends Controller

{
    use AuthenticatesUsers;

    protected $redirectTo = null;

    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function __construct()

    {

        $this->middleware('guest')->except('logout');

    }

    public function showLoginForm()
    {
        //getting theme
        if(isset($_COOKIE['theme']))
            $theme = $_COOKIE['theme'];
        else
            $theme = 'light';
        //get general setting value
        $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
            return DB::table('general_settings')->latest()->first();
        });

        $numberOfUserAccount = \App\Models\User::where('is_active', true)->count();
        return view('backend.auth.login', compact('theme', 'general_setting', 'numberOfUserAccount'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        $login = trim($credentials['name']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $user = User::where($field, $login)->first();

        if (!$user) {
            return back()
                ->withErrors(['name' => 'Username is incorrect.'])
                ->onlyInput('name');
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'Password is incorrect.'])
                ->onlyInput('name');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if(config('database.connections.saleprosaas_landlord')) {
            tenant()->update(['last_login_at' => Carbon::now()->timezone(config('app.timezone'))->format('Y-m-d h:i:s A')]);
        }

        setcookie('login_now', 1, time() + (86400 * 1), "/");

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login'); // Replace with your desired URL
    }
}
