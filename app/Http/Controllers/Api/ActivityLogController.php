<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $query = DB::table('activity_logs')
                ->join('users', 'activity_logs.user_id', '=', 'users.id')
                ->select('activity_logs.*', 'users.name as user_name');

            if (Auth::user()->role_id > 2) {
                $query->where('activity_logs.user_id', Auth::id());
            }

            $logs = $query->orderBy('activity_logs.id', 'desc')->paginate($limit);

            $rows = collect($logs->items())->map(function ($log) {
                // Assuming standard columns description, properties, or similar
                // If it's custom table 'activity_logs' not spatie:
                // Check if 'description' exists, or 'action'.
                // Safe bet:
                return [
                    'id' => $log->id,
                    'date' => date(config('date_format'), strtotime($log->created_at)),
                    'user' => $log->user_name,
                    'description' => $log->description ?? 'N/A', // Assuming description column
                ];
            });

            return $this->withDashBackground([
                'title' => 'Activity Log',
                'row_height' => 5,
                'columns' => [
                    ['label' => 'Date', 'field' => 'date', 'type' => 'text'],
                    ['label' => 'User', 'field' => 'user', 'type' => 'text'],
                    ['label' => 'Description', 'field' => 'description', 'type' => 'text'],
                ],
                'rows' => $rows,
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ], 'app');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
