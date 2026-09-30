<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Courier;

class CourierController extends Controller
{

    public function index()
    {
        $lims_courier_all = Courier::where('is_active', true)->orderBy('id', 'desc')->get();
        return view('backend.courier.index', compact('lims_courier_all'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['is_active'] = true;
        Courier::create($data);

        return redirect()->back()->with('message', __('db.Courier created successfully'));
    }

    public function update(Request $request, $id)
{
    $courier = Courier::find($id);

    $type = $request->type;

    // username ও password resolve করো type অনুযায়ী
    $username = null;
    $password = null;

    if ($type === 'pathao') {
        $username = $request->pathao_username;
        $password = $request->pathao_password;
    } elseif ($type === 'paperfly') {
        $username = $request->paperfly_username;
        $password = $request->paperfly_password;
    }

    $courier->update([
        'name'          => $request->name,
        'type'          => $request->type,
        'phone_number'  => $request->phone_number,
        'address'       => $request->address,
        // Steadfast
        'api_key'       => $request->api_key,
        'secret_key'    => $request->secret_key,
        // Pathao
        'client_id'     => $request->client_id,
        'client_secret' => $request->client_secret,
        'base_url'      => $request->base_url,
        // Redx
        'api_token'     => $request->api_token,
        // Shared (Pathao / Paperfly)
        'username'      => $username,
        'password'      => $password,
    ]);

    return redirect()->back()->with('message', __('db.Courier updated successfully'));
}

    public function destroy($id)
    {
        Courier::find($id)->update(['is_active' => false]);
        return redirect()->back()->with('not_permitted', __('db.Courier deleted successfully'));
    }
}
