<?php

namespace App\Http\Controllers;

use App\Models\FreshBusinessResetManifest;
use App\Services\FreshBusinessResetService;
use Illuminate\Http\Request;

class FreshBusinessResetController extends Controller
{
    public function __construct(private FreshBusinessResetService $service) {}

    public function index(Request $request)
    {
        abort_unless($request->user() && $request->user()->can('business_reset'), 403);
        return view('backend.setting.fresh_business_reset', [
            'preview' => $this->service->preview(),
            'manifests' => FreshBusinessResetManifest::latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user() && $request->user()->can('business_reset'), 403);
        if (!config('app.user_verified')) return back()->with('not_permitted', __('db.This feature is disable for demo!'));
        
        $data = $request->validate([
            'confirmation' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($data['confirmation'] !== 'RESET BUSINESS') {
            return back()->with('not_permitted', __('db.fresh_reset_wrong_phrase'));
        }

        if (!\Illuminate\Support\Facades\Hash::check($data['password'], $request->user()->password)) {
            return back()->with('not_permitted', __('db.fresh_reset_wrong_password'));
        }

        try {
            $this->service->execute((int) $request->user()->id, $data['confirmation']);
            return redirect()->route('setting.freshBusinessReset')->with('message', __('db.fresh_reset_success'));
        } catch (\Throwable $e) {
            report($e);
            return back()->with('not_permitted', __('db.fresh_reset_failed'));
        }
    }
}
