<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Courier;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SteadFastController extends Controller
{
    public function getSaleForSteadFast($sale_id)
    {
        $lims_sale_data = Sale::find($sale_id);
        
        $data['invoice'] = $lims_sale_data->reference_no;
        $data['recipient_name'] = $lims_sale_data->customer->name;
        $data['recipient_email'] = $lims_sale_data->customer->email;
        $data['recipient_phone'] = $lims_sale_data->customer->phone_number;
        $data['recipient_address'] = $lims_sale_data->customer->address . ', ' . $lims_sale_data->customer->city . ', ' . $lims_sale_data->customer->country;
        $data['cod_amount'] = $lims_sale_data->grand_total;

        return $data;
    }

    public function show($sale_id)
    {
        try {
            $sale = Sale::findOrFail($sale_id);
            $steadfast = Courier::where('name', 'SteadFast')->first();

            if (!$steadfast) {
                return view('backend.delivery.steadfast.delivery_status')->with([
                    'status' => 404,
                    'delivery_label' => 'SteadFast courier credentials not found.',
                ]);
            }

            $response = Http::withHeaders([
                'Api-Key'      => $steadfast->api_key,
                'Secret-Key'   => $steadfast->secret_key,
                'Content-Type' => 'application/json',
            ])->get("https://portal.packzy.com/api/v1/status_by_invoice/" . $sale->reference_no);

            if (!$response->successful()) {
                return view('backend.delivery.steadfast.delivery_status')->with([
                    'status' => $response->status(),
                    'delivery_label' => $response->json()['message'] ?? 'Failed to fetch status',
                ]);
            }

            $status = $response->json()['delivery_status'] ?? 'unknown';

            $labels = [
                'pending'                         => 'Pending',
                'delivered_approval_pending'     => 'Delivered',
                'partial_delivered_approval_pending' => 'Partially Delivered',
                'cancelled_approval_pending'     => 'Cancelled',
                'unknown_approval_pending'       => 'Unknown',
                'delivered'                      => 'Delivered',
                'partial_delivered'              => 'Partially Delivered',
                'cancelled'                      => 'Cancelled',
                'hold'                           => 'On Hold',
                'in_review'                      => 'In Review',
                'unknown'                        => 'Unknown',
            ];

            return view('backend.delivery.steadfast.delivery_status')->with([
                'status'          => 200,
                'delivery_label'  => $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)),
            ]);

        } catch (\Exception $e) {
            return view('backend.delivery.steadfast.delivery_status')->with([
                'status' => 500,
                'delivery_label' => $e->getMessage(),
            ]);
        }
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice' => 'required|string|max:100',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|digits:11',
            'alternative_phone' => 'nullable|digits:11',
            'recipient_email' => 'nullable|email',
            'recipient_address' => 'required|string|max:250',
            'cod_amount' => 'required|numeric|min:0',
            'item_description' => 'nullable|string',
            'note' => 'nullable|string',
            'total_lot' => 'nullable|numeric',
            'delivery_type' => 'nullable|in:0,1',
        ]);

        $steadfast = Courier::where('name', 'SteadFast')->first();

        if (!$steadfast) {
            return redirect()->back()->withErrors(['not_permitted' => 'SteadFast courier credentials not found']);
        }

        DB::beginTransaction();

        try {
            $response = Http::withHeaders([
                'Api-Key'     => $steadfast->api_key,
                'Secret-Key'  => $steadfast->secret_key,
                'Content-Type'=> 'application/json',
            ])->post('https://portal.packzy.com/api/v1/create_order', $validated);
            

            if ($response->successful()) {
                $data = $response->json()['consignment'];

                $sale = Sale::find($request->sale_id);
                if ($sale) {
                    $sale->steadfast = true;
                    $sale->save();
                }

                $delivery = Delivery::where('sale_id', $request->sale_id)->firstOrFail();
                $delivery->sale_id = $request->sale_id;
                $delivery->user_id = Auth::id();
                $delivery->courier_id = $steadfast->id;
                $delivery->address = $data['recipient_address'];
                $delivery->recieved_by = $data['recipient_name'];
                $delivery->status = $data['status'];
                $delivery->note = $data['note'];
                $delivery->save();

                DB::commit();

                return redirect('delivery')->with('message', 'Delivery created successfully!');
            }

            DB::rollBack();
            return redirect()->back()->withErrors(['not_permitted' => 'Something went wrong']);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withError(['not_permitted' => $e->getMessage()]);
        }
    }
}
