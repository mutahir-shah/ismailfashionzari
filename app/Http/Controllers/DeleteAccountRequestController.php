<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeleteAccountRequest;
use App\Models\GeneralSetting;
use App\Models\landlord\Tenant;

class DeleteAccountRequestController extends Controller
{
    public function show()
    {
        if (config('database.connections.saleprosaas_landlord') && !tenant()) {
            $general_setting = cache()->get('general_setting');
            $theme = $general_setting->theme ?? 'light';
            $tenants = Tenant::all();
        } else {
            $general_setting = cache()->get('general_setting');
            $theme = $general_setting->theme ?? 'light';
            $tenants = [];
        }
        return view('delete_account', compact('general_setting', 'theme', 'tenants'));
    }

    public function submit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reason' => 'nullable|string',
        ]);

        DeleteAccountRequest::create($request->only('email', 'reason'));

        return back()->with('success', 'Your account deletion request has been submitted successfully.');
    }
}
