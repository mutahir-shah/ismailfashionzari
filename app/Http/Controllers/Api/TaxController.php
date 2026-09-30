<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ErrorResource;
use App\Http\Requests\TaxRequest;
use App\Models\Tax;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class TaxController extends Controller
{
    use \App\Traits\CacheForget;
    use ProvidesThemeBackgrounds;

    public function index()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access taxes
            if (!$role->hasPermissionTo('tax')) {
                return new ErrorResource([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }
            $taxes = Tax::where('is_active', true)->orderBy('id', 'desc')->get();

            // Format taxes for datatable
            $taxRows = $taxes->map(function ($tax) {
                return [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'rate' => $tax->rate . ' %',
                ];
            });

            return $this->withDashBackground([
                'title' => "Taxes",
                'debug_bar' => env("APP_DEBUG", false) ? true : false,
                'row_height' => 3,
                'add_text' => 'Add Tax',
                'add_url' => '/taxes/create',
                'import_url' => '/taxes/import',
                'columns' => [
                    ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                    ['label' => 'Rate (%)', 'field' => 'rate', 'type' => 'text'],
                    [
                        'label' => 'Manage',
                        'type' => 'row',
                        'children' => [
                            [
                                'type' => 'action',
                                'icon' => 'edit',
                                'action' => [
                                    'api_url' => '/taxes/{id}/edit',
                                    'type' => 'form'
                                ]
                            ],
                            [
                                'type' => 'action',
                                'icon' => 'delete',
                                'action' => [
                                    'api_url' => '/taxes/{id}',
                                    'type' => 'delete'
                                ]
                            ],
                        ]
                    ],
                ],
                'rows' => $taxRows,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'success' => false,
                'message' => 'An error occurred while retrieving taxes data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
        $formSchema = [
            "title" => "Add Tax",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/taxes",
            "method" => "POST",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Tax Name",
                ],
                [
                    "type" => "text",
                    "name" => "rate",
                    "label" => "Rate(%)",
                    "keyboard_type" => "number",
                ],
                [
                    "type" => "hidden",
                    "name" => "is_active",
                    "value" => 1,
                ]
            ]
        ];
        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function store(TaxRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = true;

        $tax = Tax::create($data);

        $this->cacheForget('tax_list');

        return response()->json([
            'success' => true,
            'message' => 'Tax created successfully.',
            'navigate_url' => '/taxes',
        ], 201);
    }

    public function edit(Tax $tax)
    {
        $formSchema = [
            "title" => "Edit Tax",
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
            "submit_url" => "/taxes/" . $tax->id,
            "method" => "PUT",
            "fields" => [
                [
                    "type" => "text",
                    "name" => "name",
                    "label" => "Tax Name",
                    "value" => $tax->name
                ],
                [
                    "type" => "text",
                    "name" => "rate",
                    "label" => "Rate(%)",
                    "keyboard_type" => "number",
                    "value" => $tax->rate,
                ],
            ]
        ];
        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function update(TaxRequest $request, Tax $tax)
    {
        $data = $request->validated();

        $tax->update($data);

        $this->cacheForget('tax_list');

        return response()->json([
            'success' => true,
            'message' => 'Tax updated successfully.',
            'navigate_url' => '/taxes',
        ], 200);
    }

    public function import(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create taxes
            if (!$role->hasPermissionTo('tax-add')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to import taxes.',
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

                    $tax = Tax::firstOrNew(['name' => $data['name'], 'is_active' => true]);
                    $tax->name = $data['name'];
                    $tax->rate = $data['rate'];
                    $tax->is_active = true;
                    $tax->save();

                    $importedCount++;
                }

                fclose($file);
                $this->cacheForget('tax_list');

                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} taxes.",
                    'navigate_url' => '/taxes',
                    'debug_bar' => env('APP_DEBUG', false) ? true : false,
                ], 200);
            }

            // Handle GET request - return import form schema
            $formSchema = [
                "title" => "Import Taxes",
                "submit_url" => "/taxes/import",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "helpertext",
                        "text" => "The correct column order is (name, rate) and you must follow this.",
                    ],
                    [
                        "type" => "importdata",
                        "name" => "file",
                        "hint_text" => "Upload CSV File",
                        "file_link" => url('sample_file/sample_tax.csv'),
                        "sample_file_name" => "sample_tax.csv",
                        "download_title" => "Download Sample File",
                    ],
                ],
            ];

            return response()->json($formSchema, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Tax $tax)
    {
        $tax->is_active = false;
        $tax->save();
        $this->cacheForget('tax_list');

        return response()->json([
            'success' => true,
            'message' => 'Tax has been deleted successfully.',
            'navigate_url' => '/taxes',
        ], 200);
    }
}
