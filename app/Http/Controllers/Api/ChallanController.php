<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Models\Challan;
use App\Models\ChallanProduct;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\Variant;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use DB;

class ChallanController extends Controller
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

            $query = Challan::with(['sale', 'delivery', 'products']);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('sale', function ($sq) use ($search) {
                            $sq->where('reference_no', 'LIKE', "%{$search}%");
                        });
                });
            }

            $challans = $query->orderBy('id', 'desc')->get();

            $challansTable = $challans->map(function ($challan) {
                $itemList = '';
                $challanProducts = ChallanProduct::where('challan_id', $challan->id)->get();

                foreach ($challan->products as $index => $product) {
                    $variantId = $challanProducts[$index]->variant_id ?? null;
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

                $statusBadge = '<div class="badge badge-warning">' . $challan->status . '</div>';
                if ($challan->status == 'Delivered') {
                    $statusBadge = '<div class="badge badge-success">' . $challan->status . '</div>';
                }

                return [
                    'id' => $challan->id,
                    'reference' => 'C' . $challan->reference_no,
                    'sale_reference' => $challan->sale->reference_no ?? 'N/A',
                    'delivery_reference' => $challan->delivery->reference_no ?? 'N/A',
                    'amount' => number_format($challan->amount, 2),
                    'item_list' => $itemList,
                    'status' => $statusBadge,
                ];
            });

            return $this->withDashBackground([
                'title' => "Challans",
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Challan Ref', 'field' => 'reference', 'type' => 'text'],
                    ['label' => 'Sale Reference', 'field' => 'sale_reference', 'type' => 'text'],
                    ['label' => 'Delivery Reference', 'field' => 'delivery_reference', 'type' => 'text'],
                    ['label' => 'Amount', 'field' => 'amount', 'type' => 'text'],
                    ['label' => 'Items', 'field' => 'item_list', 'type' => 'text'],
                    ['label' => 'Status', 'field' => 'status', 'type' => 'html'],
                ],
                'rows' => $challansTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving challan data.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
