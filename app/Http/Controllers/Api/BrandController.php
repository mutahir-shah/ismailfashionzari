<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Brand;
use Illuminate\Validation\Rule;
use App\Traits\TenantInfo;
use App\Traits\CacheForget;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    use CacheForget;
    use TenantInfo;
    use ProvidesThemeBackgrounds;

    public function test()
    {
        return 'test success!';
    }

    public function index()
    {
        try {
            // Fetch all active brands
            // $brands = Brand::where('is_active', true)->get();
            $brands = Brand::where('is_active', true)->get();

            return $this->withDashBackground([
                'title' => "Brands",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Brand',
                'add_url' => '/brands/create',
                'columns' => [
                    [
                        'label' => 'Image',
                        'field' => 'image_url',
                        'type' => 'image',
                    ],
                    [
                        'label' => 'Title',
                        'field' => 'title',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/brands/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/brands/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => BrandResource::collection($brands),
            ], 'app');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve brands.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        // Get general settings for module checks
        $general_setting = DB::table('general_settings')->select('modules')->first();

        $formSchema = [
            "title" => "Add Brand",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/brands",
            "method" => "POST",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "title",
                    "label" => "Brand Title",
                    "placeholder" => "Type brand title",
                ],
                [
                    "type" => "file",
                    "name" => "image",
                    "label" => "Brand Image",
                    "allowed_extensions" => ["jpeg", "jpg", "png"],
                    "info" => "Only .jpeg, .jpg, .png file can be uploaded.",
                    "show_info_icon" => true,
                ],
            ]
        ];

        // Add SEO fields if ecommerce module is enabled
        if (in_array('ecommerce', explode(',', $general_setting->modules))) {
            $formSchema['fields'][] = [
                "type" => "group",
                "label" => "SEO",
                "items" => [
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
                    ],
                ],
            ];
        }

        $formSchema['fields'][] = [
            "type" => "hidden",
            "name" => "is_active",
            "value" => 1,
        ];

        return $this->withDashBackground($formSchema, 'app');
    }

    public function store(Request $request)
    {
        try {
            // Trim and sanitize the title
            $request->merge(['title' => preg_replace('/\s+/', ' ', $request->title)]);

            // Validation
            $validator = Validator::make($request->all(), [
                'title' => [
                    'required',
                    'max:255',
                    Rule::unique('brands')->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240', // 10MB max size
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Prepare input data
            $input = $request->except('image', 'token');
            $input['is_active'] = true;

            // Generate slug if the ecommerce addon is enabled
            if (in_array('ecommerce', explode(',', config('addons')))) {
                $input['slug'] = Str::slug($request->title, '-');
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $ext = $image->getClientOriginalExtension();
                $timestamp = date("Ymdhis");
                $imageName = $timestamp . '.' . $ext;

                // Handle tenant-specific naming
                if (config('database.connections.saleprosaas_landlord')) {
                    $imageName = $this->getTenantId() . '_' . $imageName;
                }

                $image->move(public_path('images/brand'), $imageName);
                $input['image'] = $imageName;
            }

            // Create the brand
            $brand = Brand::create($input);

            // Clear cache if necessary
            $this->cacheForget('brand_list');

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully.',
                'navigate_url' => '/brands',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the brand.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $brand = Brand::where('is_active', true)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Brand retrieved successfully.',
                'data' => new BrandResource($brand),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve brand.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);

        // Get general settings for module checks
        $general_setting = DB::table('general_settings')->select('modules')->first();

        $formSchema = [
            "title" => "Edit Brand",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/brands/{$id}",
            "method" => "PUT",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "title",
                    "label" => "Brand Title",
                    "placeholder" => "Type brand title",
                    "value" => $brand->title,
                ],
                [
                    "type" => "file",
                    "name" => "image",
                    "label" => "Brand Image",
                    "allowed_extensions" => ["jpeg", "jpg", "png"],
                    "info" => "Only .jpeg, .jpg, .png file can be uploaded.",
                    "show_info_icon" => true,
                ],
            ]
        ];

        // Add SEO fields if ecommerce module is enabled
        if (in_array('ecommerce', explode(',', $general_setting->modules))) {
            $formSchema['fields'][] = [
                "type" => "group",
                "label" => "SEO",
                "items" => [
                    [
                        "type" => "text",
                        "name" => "page_title",
                        "label" => "Meta Title",
                        "placeholder" => "Meta Title",
                        "value" => $brand->page_title ?? '',
                    ],
                    [
                        "type" => "text",
                        "name" => "short_description",
                        "label" => "Meta Description",
                        "placeholder" => "Meta Description",
                        "value" => $brand->short_description ?? '',
                    ],
                ],
            ];
        }

        $formSchema['fields'][] = [
            "type" => "hidden",
            "name" => "is_active",
            "value" => 1,
        ];

        return $this->withDashBackground($formSchema, 'app');
    }

    public function update(Request $request, $id)
    {
        try {
            // Validate the request data
            $this->validate($request, [
                'title' => [
                    'max:255',
                    Rule::unique('brands')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', 1);
                    }),
                ],
                'image' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);

            // Find the brand by ID
            $brand = Brand::findOrFail($id);

            // Update the title
            $brand->title = $request->title;

            // Check for additional fields if 'ecommerce' addon is enabled
            if (in_array('ecommerce', explode(',', config('addons')))) {
                $brand->page_title = $request->page_title;
                $brand->short_description = $request->short_description;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $ext = $image->getClientOriginalExtension();
                $imageName = date("Ymdhis");

                if (!config('database.connections.saleprosaas_landlord')) {
                    $imageName = $imageName . '.' . $ext;
                    $image->move(public_path('images/brand'), $imageName);
                } else {
                    $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    $image->move(public_path('images/brand'), $imageName);
                }
                $brand->image = $imageName;
            }

            // Save the updated brand
            $brand->save();

            // Clear brand cache
            $this->cacheForget('brand_list');

            // Return a success response
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully.',
                'navigate_url' => '/brands',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle the case where the brand is not found
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        try {
            // Find the brand by ID or fail
            $brand = Brand::findOrFail($id);

            // Set brand as inactive (soft delete)
            $brand->is_active = false;

            // Check if an image exists and delete it if necessary
            if ($brand->image) {
                $imagePath = public_path('images/brand/' . $brand->image);

                if (file_exists($imagePath)) {
                    unlink($imagePath); // Delete the image
                }
            }

            // Save the updated brand
            $brand->save();

            // Clear the brand list cache
            $this->cacheForget('brand_list');

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.',
                'navigate_url' => '/brands',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle the case where the brand is not found
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
