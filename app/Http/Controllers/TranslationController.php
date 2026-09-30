<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('name')->get();
        $defaultLanguage = Language::getDefaultLanguage();
        $translations = Translation::where('locale', $defaultLanguage->language ?? 0)
            ->orderBy('key')
            ->get();
        
        return view('vendor.translation.index', compact('languages', 'translations'));
    }

    public function fetchByLanguage($locale)
    {
        $translations = Translation::where('locale', $locale)
            ->orderBy('key')
            ->get();

        return response()->json($translations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'locale' => 'required|exists:languages,language',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);
        
        Translation::create($request->all());

        // forget cached values
        Translation::forgetCachedTranslations();

        return response()->json(['success' => 'Translation added successfully.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);
        
        $updated = Translation::where('id', $id)
        ->update([
            'key' => $request->key,
            'value' => $request->value
        ]);

        // forget cached values
        Translation::forgetCachedTranslations();

        return response()->json(['message' => 'Translation updated successfully']);
    }


    public function destroy($id)
    {
        Translation::findOrFail($id)->delete();
        return response()->json(['success' => 'Translation deleted successfully.']);
    }
}
