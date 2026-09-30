<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use  App\Http\Resources\BillerResource;
use App\Http\Resources\DefaultDataCollection;
use App\Http\Resources\ErrorResource;
use Illuminate\Http\Request;
use App\Models\Biller;
use App\Models\MailSetting;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Mail\BillerCreate;
use Illuminate\Support\Facades\Auth;
use Mail;

class BillerController extends Controller
{
    use \App\Traits\CacheForget;
    use \App\Traits\TenantInfo;
    use \App\Traits\MailInfo;
    use \App\Traits\APIPaginationTrait;
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('billers-index')) {
            $permissions = $role->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if (empty($all_permission))
                $all_permission[] = 'dummy text';
            $query = biller::where('is_active', true);
            $lims_biller_all = $this->resolveCollection($query, $request);
            $pagination = $this->resolvePagination($query, $request);

            return $this->withDashBackground([
                'title' => "Billers",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Biller',
                'add_url' => '/billers/create',
                'columns' => [
                    ['label' => 'Image', 'field' => 'image_url', 'type' => 'image'],
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Company Name', 'field' => 'company_name', 'type' => 'text'],
                    ['label' => 'VAT Number', 'field' => 'vat_number', 'type' => 'text'],
                    ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
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
                                    'api_url' => '/billers/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/billers/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => BillerResource::collection($lims_biller_all),
                'pagination' => $pagination
            ], 'app');
        } else {
            return response()->json(new ErrorResource(__('db.Sorry! You are not allowed to access this module')));
        }
    }

    public function create()
    {
        $formSchema = [
            "title" => "Add a New Biller",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/billers",
            "method" => "POST",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Biller Name",
                    "placeholder" => "Enter Biller Name",
                ],
                [
                    "type" => "file",
                    "name" => "image",
                    "label" => "Image",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter company name",
                ],
                [
                    "type" => "text",
                    "name" => "vat_number",
                    "label" => "VAT Number",
                    "placeholder" => "Enter VAT number",
                    "keyboard_type" => "number",
                ],
                [
                    "type" => "text",
                    "name" => "email",
                    "label" => "Email",
                    "placeholder" => "Enter email",
                    "keyboard_type" => "email",
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
                ],
                [
                    "type" => "text",
                    "name" => "city",
                    "label" => "City",
                    "placeholder" => "Enter city",
                ],
                [
                    "type" => "text",
                    "name" => "state",
                    "label" => "State",
                    "placeholder" => "Enter state",
                ],
                [
                    "type" => "text",
                    "name" => "postal_code",
                    "label" => "Postal Code",
                    "placeholder" => "Enter postal code",
                ],
                [
                    "type" => "text",
                    "name" => "country",
                    "label" => "Country",
                    "placeholder" => "Enter country",
                ],
                [
                    "type" => "hidden",
                    "name" => "is_active",
                    "value" => 1,
                ],
            ],
        ];

        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function edit($id)
    {
        $biller = Biller::findOrFail($id);

        $formSchema = [
            "title" => "Edit Biller",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/billers/{$id}",
            "method" => "PUT",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Biller Name",
                    "placeholder" => "Enter Biller Name",
                    "value" => $biller->name,
                ],
                [
                    "type" => "file",
                    "name" => "image",
                    "label" => "Image",
                    "allowed_extensions" => ["jpeg", "jpg", "png", "gif"],
                    "multiple" => false,
                ],
                [
                    "type" => "text",
                    "name" => "company_name",
                    "label" => "Company Name",
                    "placeholder" => "Enter company name",
                    "value" => $biller->company_name,
                ],
                [
                    "type" => "text",
                    "name" => "vat_number",
                    "label" => "VAT Number",
                    "placeholder" => "Enter VAT number",
                    "keyboard_type" => "number",
                    "value" => $biller->vat_number,
                ],
                [
                    "type" => "text",
                    "name" => "email",
                    "label" => "Email",
                    "placeholder" => "Enter email",
                    "keyboard_type" => "email",
                    "value" => $biller->email,
                ],
                [
                    "type" => "text",
                    "name" => "phone_number",
                    "label" => "Phone Number",
                    "placeholder" => "Enter phone number",
                    "keyboard_type" => "phone",
                    "value" => $biller->phone_number,
                ],
                [
                    "type" => "text",
                    "name" => "address",
                    "label" => "Address",
                    "placeholder" => "Enter address",
                    "value" => $biller->address,
                ],
                [
                    "type" => "text",
                    "name" => "city",
                    "label" => "City",
                    "placeholder" => "Enter city",
                    "value" => $biller->city,
                ],
                [
                    "type" => "text",
                    "name" => "state",
                    "label" => "State",
                    "placeholder" => "Enter state",
                    "value" => $biller->state,
                ],
                [
                    "type" => "text",
                    "name" => "postal_code",
                    "label" => "Postal Code",
                    "placeholder" => "Enter postal code",
                    "value" => $biller->postal_code,
                ],
                [
                    "type" => "text",
                    "name" => "country",
                    "label" => "Country",
                    "placeholder" => "Enter country",
                    "value" => $biller->country,
                ],
                [
                    "type" => "hidden",
                    "name" => "is_active",
                    "value" => 1,
                ],
            ],
        ];

        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function store(Request $request)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('billers-add')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $this->validate($request, [
                'name' => 'required|max:255',
                'email' => 'required|email|unique:billers,email',
                'phone_number' => 'required|max:255',
                'company_name' => 'required|max:255',
            ]);

            $data = $request->all();
            $data['is_active'] = true;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $imageName = $imageName . '.' . $ext;
                    $image->move(public_path('images/biller'), $imageName);
                } else {
                    $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    $image->move(public_path('images/biller'), $imageName);
                }
                $data['image'] = $imageName;
            }

            $biller = Biller::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Biller created successfully',
                'navigate_url' => '/billers',
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the biller.',
                'error' => env('APP_DEBUG', false) ? $e->getMessage() : null,
                'trace' => env('APP_DEBUG', false) ? $e->getTraceAsString() : null,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('billers-index')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $biller = Biller::where('is_active', true)->find($id);
            if (!$biller) {
                return response()->json(new ErrorResource('Biller not found.'), 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Biller retrieved successfully',
                'data' => new BillerResource($biller)
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while retrieving the biller.'), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('billers-edit')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $biller = Biller::find($id);
            if (!$biller || !$biller->is_active) {
                return response()->json(new ErrorResource('Biller not found.'), 404);
            }

            $this->validate($request, [
                'name' => 'required|max:255',
                'email' => 'required|email|unique:billers,email,' . $id,
                'phone_number' => 'required|max:255',
                'company_name' => 'required|max:255',
            ]);

            $data = $request->all();

            if ($request->hasFile('image')) {
                $this->fileDelete(public_path('images/biller'), $biller->image);
                $image = $request->file('image');
                $ext = pathinfo($image->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis");
                if (!config('database.connections.saleprosaas_landlord')) {
                    $imageName = $imageName . '.' . $ext;
                    $image->move(public_path('images/biller'), $imageName);
                } else {
                    $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                    $image->move(public_path('images/biller'), $imageName);
                }
                $data['image'] = $imageName;
            }

            $biller->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Biller updated successfully',
                'navigate_url' => '/billers'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while updating the biller.'), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find(Auth::user()->role_id);
            if (!$role->hasPermissionTo('billers-delete')) {
                return response()->json(new ErrorResource('Sorry! You are not allowed to access this module.'), 403);
            }

            $biller = Biller::find($id);
            if (!$biller || !$biller->is_active) {
                return response()->json(new ErrorResource('Biller not found.'), 404);
            }

            // Deactivate the biller
            $biller->is_active = false;
            $biller->save();

            return response()->json([
                'success' => true,
                'message' => 'Biller deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(new ErrorResource('An error occurred while deleting the biller.'), 500);
        }
    }
}
