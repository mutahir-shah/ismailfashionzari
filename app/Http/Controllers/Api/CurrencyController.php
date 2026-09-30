<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrencyResource;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\CurrencyRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Currency;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Auth;
use Cache;

class CurrencyController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the currency module
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $currencies = Currency::where('is_active', true)->orderBy('id', 'desc')->get();

            return $this->withDashBackground([
                'title' => "Currencies",
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                'row_height' => 5,
                'add_text' => 'Add Currency',
                'add_url' => '/currencies/create',
                'import_url' => '/currencies/import',
                'columns' => [
                    [
                        'label' => 'Currency Name',
                        'field' => 'name',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Currency Code',
                        'field' => 'code',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Exchange Rate',
                        'field' => 'exchange_rate',
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
                                    'api_url' => '/currencies/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/currencies/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => CurrencyResource::collection($currencies),
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving currency data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Currency",
                "submit_url" => "/currencies",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter currency name",
                    ],
                    [
                        "type" => "text",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter currency code",
                    ],
                    [
                        "type" => "text",
                        "name" => "exchange_rate",
                        "label" => "Exchange Rate",
                        "keyboard_type" => "number",
                        "placeholder" => "Enter exchange rate",
                        "info" => "If this is your default currency, the exchange rate must be 1",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading currency form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(CurrencyRequest $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $currency = Currency::create($request->validated());
            cache()->forget('currency');

            return response()->json([
                'success' => true,
                'message' => 'Currency created successfully.',
                'navigate_url' => '/currencies'
            ], 201);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while creating the currency.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Currency $currency)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to view currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new CurrencyResource($currency),
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving currency details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $currency = Currency::findOrFail($id);
            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Currency",
                "submit_url" => "/currencies/{$id}",
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "text",
                        "name" => "name",
                        "label" => "Name",
                        "placeholder" => "Enter currency name",
                        "value" => $currency->name,
                    ],
                    [
                        "type" => "text",
                        "name" => "code",
                        "label" => "Code",
                        "placeholder" => "Enter currency code",
                        "value" => $currency->code,
                    ],
                    [
                        "type" => "text",
                        "name" => "exchange_rate",
                        "label" => "Exchange Rate",
                        "keyboard_type" => "number",
                        "placeholder" => "Enter exchange rate",
                        "info" => "If this is your default currency, the exchange rate must be 1",
                        "show_info_icon" => true,
                        "value" => $currency->exchange_rate,
                    ],
                    [
                        "type" => "hidden",
                        "name" => "is_active",
                        "value" => 1,
                    ],
                ],
            ];
            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while loading currency edit form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(CurrencyRequest $request, Currency $currency)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $data = $request->validated();
            if ($data['exchange_rate'] == 1) {
                GeneralSetting::latest()->first()->update(['currency' => $currency->id]);
            }
            $currency->update($data);
            cache()->forget('currency');

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully.',
                'navigate_url' => '/currencies'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while updating the currency.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create currencies
            if (!$role->hasPermissionTo('currency')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import currencies.',
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

                    $currency = Currency::firstOrNew(['code' => $data['code']]);
                    $currency->name = $data['name'];
                    $currency->code = $data['code'];
                    $currency->exchange_rate = $data['exchangerate'] ?? 1;
                    $currency->save();

                    $importedCount++;
                }

                fclose($file);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} currencies.",
                    'navigate_url' => '/currencies',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Currencies",
                "submit_url" => "/currencies/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, code, exchange_rate) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_currency.csv'),
                        "sample_file_name" => "sample_currency.csv",
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

    public function destroy(Currency $currency)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete currencies
            if (!$role->hasPermissionTo('currency')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }

            $currency->update(['is_active' => false]);
            cache()->forget('currency');

            return response()->json([
                'success' => true,
                'message' => 'Currency has been deleted successfully.',
                'navigate_url' => '/currencies'
            ], 200);
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while deleting the currency.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
