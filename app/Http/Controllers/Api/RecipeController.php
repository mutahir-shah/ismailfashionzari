<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use DB;

class RecipeController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $search = $request->input('search', '');

            $query = Product::where('is_recipe', 1)->where('is_active', true);
            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            }

            $products = $query->orderBy('id', 'desc')->paginate($limit);

            $rows = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'price' => number_format($product->price, 2),
                    'cost' => number_format($product->cost, 2),
                    'unit' => $product->unit->unit_code ?? '',
                ];
            });

            return $this->withDashBackground([
                'title' => 'Recipe List',
                'add_url' => '/recipes/create',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Code', 'field' => 'code', 'type' => 'text'],
                    ['label' => 'Price', 'field' => 'price', 'type' => 'text'],
                    ['label' => 'Cost', 'field' => 'cost', 'type' => 'text'],
                    ['label' => 'Unit', 'field' => 'unit', 'type' => 'text'],
                    ['label' => 'Action', 'type' => 'action', 'action' => [
                        'type' => 'delete',
                        'api_url' => '/recipes/{id}',
                    ]],
                ],
                'rows' => $rows,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        $products = Product::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('is_recipe')->orWhere('is_recipe', 0);
            })
            ->get()->map(function ($p) {
                return ['label' => $p->name . ' (' . $p->code . ')', 'value' => $p->id];
            });

        $formSchema = [
            'title' => 'Add Recipe',
            'submit_url' => '/recipes',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'select',
                    'name' => 'p_id',
                    'label' => 'Product',
                    'options' => $products,
                    'info' => 'Select a product to define its recipe.',
                ],
                [
                    'type' => 'table_generator',
                    'name' => 'products',
                    'label' => 'Ingredients',
                    'search_url' => '/products/lims_product_search',
                    'search_placeholder' => 'Search ingredients...',
                    'columns' => [
                        [
                            'name' => 'name',
                            'label' => 'Name',
                            'type' => 'text',
                            'editable' => false,
                            'width' => 200,
                        ],
                        [
                            'name' => 'qty',
                            'label' => 'Quantity',
                            'type' => 'number',
                            'editable' => true,
                            'default' => 1,
                        ],
                        [
                            'name' => 'cost', // Mapped from net_unit_price usually
                            'label' => 'Unit Cost',
                            'type' => 'number',
                            'editable' => true,
                            'decimal_places' => 2,
                        ],
                        [
                            'name' => 'subtotal',
                            'label' => 'Subtotal',
                            'type' => 'formula',
                            'formula' => 'qty * cost',
                            'width' => 120,
                            'decimal_places' => 2,
                        ],
                    ],
                    'totals' => [
                        [
                            'label' => 'Total Cost',
                            'formula' => 'SUM(subtotal)',
                            'position' => 'right',
                            'decimal_places' => 2,
                        ],
                    ],
                ]
            ]
        ];
        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'p_id' => 'required',
            'products' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $product = Product::findOrFail($request->p_id);

            // Process Table Generator Data
            $products_table = json_decode($request->products, true);

            $product_list = [];
            $qty_list = [];
            $price_list = [];
            $total_cost = 0;

            foreach ($products_table as $item) {
                // table_generator returns 'id' of the product (ingredient)
                $product_list[] = $item['id'];
                $qty_list[] = $item['qty'];
                $price_list[] = $item['cost'];
                $total_cost += ($item['qty'] * $item['cost']);
            }

            $data = [
                'qty_list' => implode(",", $qty_list),
                'price_list' => implode(",", $price_list),
                'product_list' => implode(",", $product_list),
                'cost' => $total_cost,
                'price' => $product->price, // Keep original price or update? Web updates it. I'll keep original.
                'is_recipe' => 1
            ];

            // Setup default values for other recipe fields to avoid errors if schema expects them
            $data['wastage_percent'] = implode(",", array_fill(0, count($product_list), 0));
            $data['variant_list'] = implode(",", array_fill(0, count($product_list), null));

            $product->update($data);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Recipe created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->update(['is_recipe' => 0]);
            return response()->json(['success' => true, 'message' => 'Recipe deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
