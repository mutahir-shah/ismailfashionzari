<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use App\Traits\CacheForget;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    use CacheForget;
    use TenantInfo;
    use ProvidesThemeBackgrounds;

    // public function index(Request $request)
    // {
    //     try {
    //         $user = Auth::user();
    //         $role = Role::find($user->role_id);

    //         // Check if the user has permission to access the category module
    //         if (!$role->hasPermissionTo('category')) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Sorry! You are not allowed to access this module.',
    //             ], 403);
    //         }

    //         $categories = Category::where('is_active', true)
    //                                 ->with('parent')
    //                                 ->orderBy('id', 'desc')
    //                                 ->get();



    //       return response()->json($categories);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while retrieving category data.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the category module
            if (!$role->hasPermissionTo('category')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            // Pagination parameters// Default limit is 10   // Default page is 1
            $search = $request->input('search', '');

            // Retrieve categories
            $query = Category::where('is_active', true)->with('parent');
            if (!empty($search)) {
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $totalData = $query->count();
            $categories = $query->orderBy('id', 'desc')->get();

            // Format categories for datatable
            $categoriesTable = $categories->map(function ($category) {
                $parentName = $category->parent_id
                    ? Category::find($category->parent_id)->name
                    : "N/A";

                $totalProducts = $category->product()->where('is_active', true);
                $totalPrice = $totalProducts->sum(DB::raw('price * qty'));
                $totalCost = $totalProducts->sum(DB::raw('cost * qty'));

                $stockWorth = config('currency_position') == 'prefix'
                    ? config('currency') . " $totalPrice / " . config('currency') . " $totalCost"
                    : "$totalPrice " . config('currency') . " / $totalCost " . config('currency');

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image_url' => $category->image
                        ? url('images/category', $category->image)
                        : url('images/zummXD2dvAtI.png'),
                    'parent_name' => $parentName,
                    'number_of_products' => "<span style='font-size: 20px;'>" . $totalProducts->count() . "</span>",
                    'stock_quantity' => "<span style='font-size: 20px;'>" . $totalProducts->sum('qty') . "</span>",
                    'stock_worth' => $stockWorth,
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Categories",
                'row_height' => 5,
                'add_text' => 'Add Category',
                'add_url' => '/categories/create',
                'import_url' => '/categories/import',
                'columns' => [
                    ['label' => 'Image', 'field' => 'image_url', 'type' => 'image'],
                    ['label' => 'Category', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Parent Category', 'field' => 'parent_name', 'type' => 'text'],
                    ['label' => 'Number of Products', 'field' => 'number_of_products', 'type' => 'html'],
                    ['label' => 'Stock Quantity', 'field' => 'stock_quantity', 'type' => 'html'],
                    ['label' => 'Stock Worth (Price/Cost)', 'field' => 'stock_worth', 'type' => 'html'],
                    ['label' => 'Manage', 'type' => 'row', 'children' => [
                        [
                            'type' => 'action',
                            'icon' => 'edit',
                            'action' => [
                                'api_url' => '/categories/{id}/edit',
                                'type' => 'form'
                            ]
                        ],
                        [
                            'type' => 'action',
                            'icon' => 'delete',
                            'action' => [
                                'api_url' => '/categories/{id}',
                                'type' => 'delete'
                            ]
                        ],
                    ]],
                ],
                'rows' => $categoriesTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving category data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        $user = Auth::user();
        $role = Role::find($user->role_id);

        if (!$role->hasPermissionTo('category')) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! You are not allowed to access this module.',
            ], 403);
        }

        $categories = Category::where('is_active', true)->get()->map(function ($category) {
            return [
                "value" => $category->id,
                "label" => $category->name,
            ];
        });

        $generalSetting = GeneralSetting::latest()->first();

        $formFields = [
            [
                "type" => "text",
                "name" => "name",
                "label" => "Name *",
                "placeholder" => "Enter category name",
                "required" => true,
            ],
            [
                "type" => "file",
                "name" => "image",
                "label" => "Image",
                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                "multiple" => false,
            ],
            [
                "type" => "select",
                "name" => "parent_id",
                "label" => "Parent Category",
                "placeholder" => "No parent",
                "options" => $categories,
            ],
        ];

        // Add WooCommerce sync disable option if column exists
        if (Schema::hasColumn('categories', 'woocommerce_category_id')) {
            $formFields[] = [
                "type" => "checkbox",
                "name" => "is_sync_disable",
                "label" => "Disable Woocommerce Sync",
                "value" => true,
            ];
        }

        // Add restaurant module fields
        if (in_array('restaurant', explode(',', $generalSetting->modules))) {
            $formFields[] = [
                "type" => "group",
                "label" => "For Website",
                "items" => [
                    [
                        "type" => "checkbox",
                        "name" => "featured",
                        "label" => "List on website",
                        "value" => true,
                    ]
                ]
            ];
        }

        // Add ecommerce module fields
        if (in_array('ecommerce', explode(',', $generalSetting->modules))) {
            $ecommerceFields = [
                [
                    "type" => "file",
                    "name" => "icon",
                    "label" => "Icon",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "checkbox",
                    "name" => "featured",
                    "label" => "List on category dropdown",
                    "value" => true,
                ]
            ];

            // Add SEO fields
            $seoFields = [
                [
                    "type" => "text",
                    "name" => "page_title",
                    "label" => "Meta Title",
                    "placeholder" => "Meta Title",
                ],
                [
                    "type" => "text",
                    "name" => "short_description",
                    "label" => "Meta Description",
                    "placeholder" => "Meta Description",
                ]
            ];

            $formFields[] = [
                "type" => "group",
                "label" => "For Website",
                "items" => $ecommerceFields
            ];

            $formFields[] = [
                "type" => "group",
                "label" => "For SEO",
                "items" => $seoFields
            ];
        }

        // Add hidden active field
        $formFields[] = [
            "type" => "hidden",
            "name" => "is_active",
            "value" => 1,
        ];

        $formSchema = [
            "title" => "Add Category",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/categories",
            "method" => "POST",
            "navigate_url" => "/categories",
            "fields" => $formFields
        ];

        return $this->withDashBackground($formSchema, 'app');
    }

    public function store(Request $request)
    {
        // Clean up parent_id - convert empty string or "null" to actual null
        if (
            $request->has('parent_id') &&
            (empty($request->parent_id) || $request->parent_id === 'null' || $request->parent_id === '')
        ) {
            $request->merge(['parent_id' => null]);
        }

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'required|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $lims_category_data = [];

        // Handle image upload
        $image = $request->image;
        if ($image) {
            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            } else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            if (!file_exists(public_path('images/category/large/'))) {
                mkdir(public_path('images/category/large/'), 0755, true);
            }
            $manager = new ImageManager(new Driver());
            $imageObj = $manager->read(public_path('images/category/' . $imageName));
            $imageObj->resize(600, 750)->save(public_path('images/category/large/' . $imageName));

            $lims_category_data['image'] = $imageName;
        }

        // Handle icon upload
        $icon = $request->icon;
        if ($icon) {
            if (!file_exists(public_path('images/category/icons/'))) {
                mkdir(public_path('images/category/icons/'), 0755, true);
            }
            $ext = pathinfo($icon->getClientOriginalName(), PATHINFO_EXTENSION);
            $iconName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $iconName = $iconName . '.' . $ext;
                $icon->move(public_path('images/category/icons/'), $iconName);
            } else {
                $iconName = $this->getTenantId() . '_' . $iconName . '.' . $ext;
                $icon->move(public_path('images/category/icons/'), $iconName);
            }

            $manager = new ImageManager(new Driver());
            $iconObj = $manager->read(public_path('images/category/icons/' . $iconName));

            $lims_category_data['icon'] = $iconName;
        }

        $lims_category_data['name'] = preg_replace('/\s+/', ' ', $request->name);
        $lims_category_data['parent_id'] = $request->parent_id;
        $lims_category_data['is_active'] = true;

        if (isset($request->is_sync_disable))
            $lims_category_data['is_sync_disable'] = $request->is_sync_disable;

        // Handle ecommerce fields
        if (in_array('ecommerce', explode(',', config('addons')))) {
            $lims_category_data['slug'] = \Illuminate\Support\Str::slug($request->name, '-');
            if ($request->featured == 1) {
                $lims_category_data['featured'] = 1;
            } else {
                $lims_category_data['featured'] = 0;
            }
            $lims_category_data['page_title'] = $request->page_title;
            $lims_category_data['short_description'] = $request->short_description;
        }

        // Handle restaurant module fields
        if (in_array('restaurant', explode(',', config('addons')))) {
            if ($request->featured == 1) {
                $lims_category_data['featured'] = 1;
            } else {
                $lims_category_data['featured'] = 0;
            }
        }

        $category = Category::create($lims_category_data);

        $this->cacheForget('category_list');

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'navigate_url' => '/categories',
            'debug_bar' => env('APP_DEBUG', false) ? true : false,
        ], 201);
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view categories
            if (!$role->hasPermissionTo('category')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $category = Category::where('is_active', true)->with('parent')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully.',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function edit($id)
    {
        $user = Auth::user();
        $role = Role::find($user->role_id);

        if (!$role->hasPermissionTo('category')) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry! You are not allowed to access this module.',
            ], 403);
        }

        $category = Category::findOrFail($id);
        $categories = Category::where('is_active', true)->where('id', '!=', $id)->get()->map(function ($cat) {
            return [
                "value" => $cat->id,
                "label" => $cat->name,
            ];
        });

        $generalSetting = GeneralSetting::latest()->first();

        $formFields = [
            [
                "type" => "text",
                "name" => "name",
                "label" => "Name *",
                "placeholder" => "Enter category name",
                "value" => $category->name,
                "required" => true,
            ],
            [
                "type" => "file",
                "name" => "image",
                "label" => "Image",
                "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                "multiple" => false,
            ],
            [
                "type" => "select",
                "name" => "parent_id",
                "label" => "Parent Category",
                "placeholder" => "No parent",
                "options" => $categories,
                "value" => $category->parent_id,
            ],
        ];

        // Add WooCommerce sync disable option if column exists
        if (Schema::hasColumn('categories', 'woocommerce_category_id')) {
            $formFields[] = [
                "type" => "checkbox",
                "name" => "is_sync_disable",
                "label" => "Disable Woocommerce Sync",
                "value" => $category->is_sync_disable ? true : false,
            ];
        }

        // Add restaurant module fields
        if (in_array('restaurant', explode(',', $generalSetting->modules))) {
            $formFields[] = [
                "type" => "group",
                "label" => "For Website",
                "items" => [
                    [
                        "type" => "checkbox",
                        "name" => "featured",
                        "label" => "List on website",
                        "value" => $category->featured ? true : false,
                    ]
                ]
            ];
        }

        // Add ecommerce module fields
        if (in_array('ecommerce', explode(',', $generalSetting->modules))) {
            $ecommerceFields = [
                [
                    "type" => "file",
                    "name" => "icon",
                    "label" => "Icon",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "checkbox",
                    "name" => "featured",
                    "label" => "List on category dropdown",
                    "value" => $category->featured ? true : false,
                ]
            ];

            // Add SEO fields
            $seoFields = [
                [
                    "type" => "text",
                    "name" => "page_title",
                    "label" => "Meta Title",
                    "placeholder" => "Meta Title",
                    "value" => $category->page_title ?? '',
                ],
                [
                    "type" => "text",
                    "name" => "short_description",
                    "label" => "Meta Description",
                    "placeholder" => "Meta Description",
                    "value" => $category->short_description ?? '',
                ]
            ];

            $formFields[] = [
                "type" => "group",
                "label" => "For Website",
                "items" => $ecommerceFields
            ];

            $formFields[] = [
                "type" => "group",
                "label" => "For SEO",
                "items" => $seoFields
            ];
        }

        $formSchema = [
            "title" => "Edit " . $category->name,
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/categories/" . $id,
            "method" => "PUT",
            "navigate_url" => "/categories",
            "fields" => $formFields
        ];

        return $this->withDashBackground($formSchema, 'app');
    }

    public function update(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'icon' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $lims_category_data = DB::table('categories')->where('id', $id)->first();

        if (!$lims_category_data) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 404);
        }

        $input = $request->except('image', 'icon', '_method', '_token', 'category_id', 'token');

        // Handle image upload
        $image = $request->image;
        if ($image) {
            $this->fileDelete(public_path('images/category/'), $lims_category_data->image);

            $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            } else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                $image->move(public_path('images/category'), $imageName);
            }
            if (!file_exists(public_path('images/category/large/'))) {
                mkdir(public_path('images/category/large/'), 0755, true);
            }

            $manager = new ImageManager(new Driver());
            $imageObj = $manager->read(public_path('images/category/' . $imageName));
            $imageObj->resize(600, 750)->save(public_path('images/category/large/' . $imageName));

            $input['image'] = $imageName;
        }

        // Handle icon upload
        $icon = $request->icon;
        if ($icon) {
            if (!file_exists(public_path('images/category/icons/'))) {
                mkdir(public_path('images/category/icons/'), 0755, true);
            }
            $this->fileDelete(public_path('images/category/icons/'), $lims_category_data->icon);

            $ext = pathinfo($icon->getClientOriginalName(), PATHINFO_EXTENSION);
            $iconName = date("Ymdhis");
            if (!config('database.connections.saleprosaas_landlord')) {
                $iconName = $iconName . '.' . $ext;
                $icon->move(public_path('images/category/icons/'), $iconName);
            } else {
                $iconName = $this->getTenantId() . '_' . $iconName . '.' . $ext;
                $icon->move(public_path('images/category/icons/'), $iconName);
            }

            $manager = new ImageManager(new Driver());
            $iconObj = $manager->read(public_path('images/category/icons/' . $iconName));

            $input['icon'] = $iconName;
        }

        // Handle featured checkbox
        if (!isset($request->featured) && \Schema::hasColumn('categories', 'featured')) {
            $input['featured'] = 0;
        }
        if (!isset($input['is_sync_disable']) && \Schema::hasColumn('categories', 'is_sync_disable'))
            $input['is_sync_disable'] = null;

        // Handle ecommerce fields
        if (in_array('ecommerce', explode(',', config('addons')))) {
            $input['slug'] = \Illuminate\Support\Str::slug($request->name, '-');
            if ($request->featured == 1) {
                $input['featured'] = 1;
            } else {
                $input['featured'] = 0;
            }
            $input['page_title'] = $request->page_title;
            $input['short_description'] = $request->short_description;
        }

        DB::table('categories')->where('id', $id)->update($input);

        $this->cacheForget('category_list');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'navigate_url' => '/categories',
            'debug_bar' => env('APP_DEBUG', false) ? true : false,
        ], 200);
    }


    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create categories
            if (!$role->hasPermissionTo('category-create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import categories.',
                ], 403);
            }

            // Handle POST request - process the import
            if ($request->isMethod('post')) {
                $request->validate([
                    'file' => 'required|file|mimes:csv',
                ]);

                $upload = $request->file('file');
                $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
                if ($ext != 'csv') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please upload a CSV file',
                    ], 400);
                }

                $filePath = $upload->getRealPath();
                $file = fopen($filePath, 'r');
                $header = fgetcsv($file);
                $escapedHeader = [];

                // Validate and escape header
                foreach ($header as $key => $value) {
                    $lheader = strtolower($value);
                    $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                    array_push($escapedHeader, $escapedItem);
                }

                $importedCount = 0;
                // Loop through rows
                while ($columns = fgetcsv($file)) {
                    if ($columns[0] == "")
                        continue;

                    $data = array_combine($escapedHeader, $columns);
                    $category = Category::firstOrNew(['name' => $data['name'], 'is_active' => true]);

                    if (isset($data['parentcategory']) && $data['parentcategory']) {
                        $parent_category = Category::firstOrNew(['name' => $data['parentcategory'], 'is_active' => true]);
                        $parent_id = $parent_category->id;
                    } else {
                        $parent_id = null;
                    }

                    $category->parent_id = $parent_id;

                    if (in_array('ecommerce', explode(',', config('addons')))) {
                        $category->slug = \Illuminate\Support\Str::slug($data['name'], '-');
                    }

                    $category->is_active = true;
                    $category->save();
                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('category_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} categories.",
                    'navigate_url' => '/categories',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Categories",
                "submit_url" => "/categories/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, parent_category) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_category.csv'),
                        "sample_file_name" => "sample_category.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'), 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $lims_category_data = Category::findOrFail($id);

            // Soft delete the category by setting `is_active` to false
            $lims_category_data->is_active = false;
            $lims_category_data->save();

            // Soft delete all products under this category
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = false;
                $product_data->save();
            }

            // Delete category image and icon if they exist
            $this->fileDelete(public_path('images/category/'), $lims_category_data->image);
            $this->fileDelete(public_path('images/category/icons/'), $lims_category_data->icon);

            $this->cacheForget('category_list');

            // Return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
                'navigate_url' => '/categories',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
