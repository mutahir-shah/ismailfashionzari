<?php

namespace Modules\AIAssistant\Http\Controllers;

use App\Http\Controllers\Controller;

class AIAssistantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $theme = env('THEME', 'default');
        if (\Schema::hasTable('general_settings')) {
            $theme = \App\Models\GeneralSetting::latest()->value('theme');
        }
        $languages = \App\Models\Language::orderBy('name')->get();
        return view('aiassistant::index', compact('theme', 'languages'));
    }
}
