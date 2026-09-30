<?php

namespace Modules\Restaurant\Http\Controllers;

use Modules\Restaurant\Entities\Reservations;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Session;
use DB; 

class ReservationController extends Controller
{

    public function index()
    {
        $reservations = Reservations::where('date','>=', date('d-m-Y'))
                                    ->join('tables','tables.id','=','reservation.table_id')
                                    ->select('reservation.*','tables.name as table')
                                    ->get();
        return view('restaurant::backend.reservation.index', compact('reservations'));
    }

    public function store(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }
        $data = $request->all();

        if (Reservations::create($data)) {
            $newdata = Reservations::orderby('id', 'DESC')->first();
            Session::flash('message', 'Reservation saved successfully.');
            Session::flash('type', 'success');
            return redirect()->back();
        } else {
            Session::flash('message', 'Failed to save reservation.');
            Session::flash('type', 'danger');
            return redirect()->back();
        }
    } 

    public function edit($id)
    {
        $reservation = Reservations::find($id);
        return $reservation;
    }

    public function update(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }
        $reservation = Reservations::findOrFail($request->reservationid);

        $reservation->name = $request->name;
        $reservation->phone = $request->phone;
        $reservation->email = $request->email;
        $reservation->date = $request->date;
        $reservation->time = $request->time;
        $reservation->person = $request->person;
        $reservation->table_id = $request->table_id;

        $reservation->save();

        Session::flash('message', 'Reservation saved successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        Reservations::findOrFail($request->id)->delete();

        Session::flash('message', 'Reservation deleted successfully.');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function check(Request $request)
    {

        if($request->type == 'add'){
            $reservations = Reservations::where('date',$request->date)->where('time',$request->time)->pluck('table_id');

            $tables = Table::join('floors','tables.floor_id','=','floors.id')
                            ->select('tables.id as id','tables.name as table','tables.number_of_person','floors.name')
                            ->where('tables.number_of_person', '>=', $request->person)
                            ->whereNotIn('tables.id',$reservations)
                            ->get();
        }else{
            $tables = Table::join('floors','tables.floor_id','=','floors.id')
                            ->select('tables.id as id','tables.name as table','tables.number_of_person','floors.name')
                            ->where('tables.number_of_person', '>=', $request->person)
                            ->get();
        }
        
        if(isset($tables)){
            return response()->json(['success' => true, 'tables' => $tables]);
        }else{
            return response()->json(['success' => false, 'message' => 'No tables available at this hours']);
        }
    }

}
