<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Models\PackingSlip;
use App\Models\PackingSlipProduct;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\Variant;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use DB;

class PackingSlipController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('packing_slip_challan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $search = $request->input('search', '');

            $query = PackingSlip::with(['sale', 'delivery', 'products']);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('sale', function ($sq) use ($search) {
                            $sq->where('reference_no', 'LIKE', "%{$search}%");
                        });
                });
            }

            $packingSlips = $query->orderBy('id', 'desc')->get();

            $packingSlipsTable = $packingSlips->map(function ($packingSlip) {
                $itemList = '';
                $packingSlipProducts = PackingSlipProduct::where('packing_slip_id', $packingSlip->id)->get();

                foreach ($packingSlip->products as $index => $product) {
                    $variantId = $packingSlipProducts[$index]->variant_id ?? null;
                    $productName = $product->name;

                    if ($variantId) {
                        $variant = Variant::find($variantId);
                        if ($variant) {
                            $productName .= ' [' . $variant->name . ']';
                        }
                    }

                    if ($index > 0) {
                        $itemList .= ', ' . $productName;
                    } else {
                        $itemList = $productName;
                    }
                }

                $statusBadge = '<div class="badge badge-warning">' . $packingSlip->status . '</div>';
                if ($packingSlip->status == 'Delivered') {
                    $statusBadge = '<div class="badge badge-success">' . $packingSlip->status . '</div>';
                }

                return [
                    'id' => $packingSlip->id,
                    'reference' => 'P' . $packingSlip->reference_no,
                    'sale_reference' => $packingSlip->sale->reference_no ?? 'N/A',
                    'delivery_reference' => $packingSlip->delivery->reference_no ?? 'N/A',
                    'amount' => number_format($packingSlip->amount, 2),
                    'item_list' => $itemList,
                    'status' => $statusBadge,
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'title' => "Packing Slips",
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Packing Slip Ref', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Sale Reference', 'field' => 'sale_reference', 'type' => 'text'],
                    ['label' => 'Delivery Reference', 'field' => 'delivery_reference', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Items', 'field' => 'item_list', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                ],
                'rows' => $packingSlipsTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving packing slip data.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
