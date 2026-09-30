<?php

namespace Modules\Restaurant\Http\Controllers;

use Modules\Restaurant\Entities\MenuType;
use Illuminate\Http\Request;
use Session;
use DB; 

class MenuTypeController extends Controller
{

    public function index()
    {
        $menu_types = MenuType::all();
        return view('restaurant::backend.menu-type.index', compact('menu_types'));
    }

    public function store(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        if (MenuType::where('name', $request->name)->exists()) {
            Session::flash('message', 'Menu Type "' . $request->name . '" already exists.');
            Session::flash('type', 'danger');
            return redirect()->back();
        }

        $data = $request->all();

        if (MenuType::create($data)) {
            Session::flash('message', 'Menu Type saved successfully.');
            Session::flash('type', 'success');
            return redirect()->back();
        } else {
            Session::flash('message', 'Failed to save menu_type.');
            Session::flash('type', 'danger');
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $menu_type = MenuType::find($id);
        return $menu_type;
    }

    public function update(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        if (MenuType::where('name', $request->name)->where('id', '!=', $request->menu_type_id)->exists()) {
            Session::flash('message', 'Menu Type "' . $request->name . '" already exists.');
            Session::flash('type', 'danger');
            return redirect()->back();
        }

        $menu_type = MenuType::findOrFail($request->menu_type_id);

        $menu_type->name = $request->name;

        $menu_type->save();

        Session::flash('message', 'Menu Type saved successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        MenuType::findOrFail($request->id)->delete();

        Session::flash('message', 'Menu Type deleted successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

}
