<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Traits\APIPaginationTrait;

class CourierController extends Controller
{

    use APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Couriers are accessible to anyone with sales-index permission
            if (!$role->hasPermissionTo('sales-index')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            $query = Courier::where('is_active', true)->orderBy('id', 'desc');
            $couriers = $this->resolveCollection($query, request());
            $pagination = $this->resolvePagination($query, request());

            // Format couriers for datatable
            $courierRows = $couriers->map(function ($courier) {
                return [
                    'id' => $courier->id,
                    'name' => $courier->name,
                    'phone_number' => $courier->phone_number ?? 'N/A',
                    'address' => $courier->address ?? 'N/A',
                ];
            });

            return $this->withDashBackground([
                'title' => "Couriers",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 4,
                'add_text' => 'Add Courier',
                'add_url' => '/couriers/create',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Phone Number', 'field' => 'phone_number', 'type' => 'text'],
                    ['label' => 'Address', 'field' => 'address', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/couriers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/couriers/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $courierRows,
                'pagination' => $pagination,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while retrieving couriers data.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }

    public function create()
    {
        try {
            $formSchema = [
                "title" => "Add Courier",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/couriers",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Courier Name",
                        "placeholder" => "Enter courier name",
                    ],
                    [
                        "type" => "text",
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "multiline" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ]
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the courier creation form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('courier')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'name' => [
                    'required',
                    'max:255',
                    Rule::unique('couriers')->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            $data = $request->all();
            $data['is_active'] = true;

            Courier::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Courier created successfully.',
                'navigate_url' => '/couriers',
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while creating the courier.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('courier')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $courier = Courier::findOrFail($id);

            $formSchema = [
                "title" => "Edit Courier",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "submit_url" => "/couriers/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Courier Name",
                        "placeholder" => "Enter courier name",
                        "value" => $courier->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "phone_number",
                        "label" => "Phone Number",
                        "placeholder" => "Enter phone number",
                        "keyboard_type" => "phone",
                        "value" => $courier->phone_number ?? '',
                    ],
                    [
                        "type" => "text",
                        "name" => "address",
                        "label" => "Address",
                        "placeholder" => "Enter address",
                        "multiline" => true,
                        "value" => $courier->address ?? '',
                    ],
                ]
            ];

            return response()->json($this->withDashBackground($formSchema, 'app'));
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the courier edit form.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('courier')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $courier = Courier::findOrFail($id);

            $request->validate([
                'name' => [
                    'required',
                    'max:255',
                    Rule::unique('couriers')->ignore($id)->where(function ($query) {
                        return $query->where('is_active', true);
                    }),
                ],
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            $courier->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Courier updated successfully.',
                'navigate_url' => '/couriers',
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while updating the courier.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            if (!$role->hasPermissionTo('courier')) {
                return response()->json(new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]), 403);
            }

            $courier = Courier::findOrFail($id);
            $courier->is_active = false;
            $courier->save();

            return response()->json([
                'success' => true,
                'message' => 'Courier deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource([
                'message' => 'An error occurred while deleting the courier.',
                'error' => $e->getMessage(),
            ]), 500);
        }
    }
}
