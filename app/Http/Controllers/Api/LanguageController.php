<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class LanguageController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access language settings
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $search = $request->input('search', '');

            // Retrieve languages
            $query = Language::query();
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('language', 'LIKE', "%{$search}%");
                });
            }

            $languages = $query->orderBy('name')->get();

            // Format languages for datatable
            $languagesTable = $languages->map(function ($language) {
                return [
                    'id' => $language->id,
                    'language' => $language->language,
                    'name' => $language->name,
                    'is_default_value' => $language->is_default,
                    'is_default' => $language->is_default
                        ? "<span style='color: green; font-weight: bold;'>Default</span>"
                        : "<span style='color: gray;'>-</span>",
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Languages",
                "row_height" => 5,
                "add_url" => "/languages/create",
                "columns" => [
                    [
                        "label" => "Locale",
                        "field" => "language",
                        "type" => "text",
                    ],
                    [
                        "label" => "Name",
                        "field" => "name",
                        "type" => "text",
                    ],
                    [
                        "label" => "Default",
                        "field" => "is_default",
                        "type" => "html",
                    ],
                    [
                        "label" => "Manage",
                        "type" => "row",
                        "children" => [
                            [
                                "type" => "button",
                                "label" => "Set Default",
                                "action" => [
                                    "api_url" => "/languages/{id}/set-default",
                                    "type" => "custom_api"
                                ],
                                "logics" => [
                                    [
                                        "field" => "is_default_value",
                                        "values" => [false, null],
                                    ],
                                ],
                            ],
                            [
                                "type" => "action",
                                "icon" => "edit",
                                "action" => [
                                    "api_url" => "/languages/{id}/edit",
                                    "type" => "form"
                                ]
                            ],
                            [
                                "type" => "action",
                                "icon" => "delete",
                                "action" => [
                                    "api_url" => "/languages/{id}",
                                    "type" => "delete"
                                ]
                            ]
                        ]
                    ]
                ],
                "rows" => $languagesTable,
            ], 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while retrieving languages.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Add Language",
                "submit_url" => "/languages",
                "method" => "POST",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Language Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "language",
                                "label" => "Language Code *",
                                "placeholder" => "Enter language code (e.g., en, fr, es)",
                                "info" => "Use standard ISO 639-1 language codes",
                                "show_info_icon" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Language Name *",
                                "placeholder" => "Enter language name (e.g., English, French, Spanish)",
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default Language",
                                "value" => false,
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the form schema.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to create languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $request->validate([
                'language' => 'required|string|max:10|unique:languages,language',
                'name' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            $data = [
                'language' => $request->language,
                'name' => $request->name,
                'is_default' => isset($request->is_default) ? true : false,
            ];

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                Language::query()->update(['is_default' => false]);
            }

            $language = Language::create($data);

            // Clear cache
            Language::forgetCachedLanguage();
            Translation::forgetCachedTranslations();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Language created successfully.',
                'data' => $language,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while creating the language.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to edit languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $language = Language::findOrFail($id);

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Edit Language",
                "submit_url" => "/languages/" . $id,
                "method" => "PUT",
                "fields" => [
                    [
                        "type" => "group",
                        "label" => "Language Information",
                        "items" => [
                            [
                                "type" => "text",
                                "name" => "language",
                                "label" => "Language Code *",
                                "placeholder" => "Enter language code (e.g., en, fr, es)",
                                "value" => $language->language,
                                "info" => "Use standard ISO 639-1 language codes",
                                "show_info_icon" => true,
                            ],
                            [
                                "type" => "text",
                                "name" => "name",
                                "label" => "Language Name *",
                                "placeholder" => "Enter language name (e.g., English, French, Spanish)",
                                "value" => $language->name,
                            ],
                            [
                                "type" => "checkbox",
                                "name" => "is_default",
                                "label" => "Set as Default Language",
                                "value" => (bool)$language->is_default,
                            ],
                        ],
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return new ErrorResource([
                'message' => 'An error occurred while loading the form schema.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to update languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $language = Language::findOrFail($id);

            $request->validate([
                'language' => 'required|string|max:10|unique:languages,language,' . $id,
                'name' => 'required|string|max:255',
            ]);

            DB::beginTransaction();

            $data = [
                'language' => $request->language,
                'name' => $request->name,
                'is_default' => isset($request->is_default) ? true : false,
            ];

            // If setting as default, unset other defaults
            if ($data['is_default']) {
                Language::where('id', '!=', $id)->update(['is_default' => false]);
            }

            $language->update($data);

            // Clear cache
            Language::forgetCachedLanguage();
            Translation::forgetCachedTranslations();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully.',
                'data' => $language,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while updating the language.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setDefault($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to modify languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            DB::beginTransaction();

            // Use the model's method to set default language
            Language::setDefaultLanguage($id);

            DB::commit();

            $language = Language::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Default language updated successfully.',
                'data' => $language,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while setting default language.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to delete languages
            if (!$role->hasPermissionTo('language_setting')) {
                return new ErrorResource([
                    'message' => 'Sorry! You are not allowed to access this module.',
                ]);
            }

            $language = Language::findOrFail($id);

            // Prevent deletion of default language
            if ($language->is_default) {
                return new ErrorResource([
                    'message' => 'Cannot delete the default language.',
                ]);
            }

            DB::beginTransaction();

            // Delete all translations for this language
            Translation::where('locale', $language->language)->delete();

            $language->delete();

            // Clear cache
            Cache::forget('default_language');
            Language::forgetCachedLanguage();
            Translation::forgetCachedTranslations();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Language deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new ErrorResource([
                'message' => 'An error occurred while deleting the language.',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
