<?php

namespace Modules\Project\Http\Controllers;

use App\Notifications\TicketAssignedNotification;
use Modules\Project\Entities\Project;
use Modules\Project\Entities\SupportTicket;
use Modules\Project\Entities\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EmployeeAssignedController extends Controller {

	public function employeeTicketAssigned(Request $request, SupportTicket $ticket)
	{
		$employees = $request->input('employee_id');
		$ticket->assignedEmployees()->sync($employees);
		$notificable = User::where('role_users_id', 1)
			->orWhere('id', $ticket->employee->id)
			->orwhereIntegerInRaw('id', $employees)
			->get();
		Notification::send($notificable, new TicketAssignedNotification($ticket));

		return response()->json(['success' => __('Data Added successfully.')]);
	}

	public function employeeProjectAssigned(Request $request, Project $project)
	{
			$employees = $request->input('employee_id');
			$project->assignedEmployees()->sync($employees);

			return response()->json(['success' => __('Data Added successfully.')]);
	}

	public function employeeTaskAssigned(Request $request, Task $task)
	{
		$employees = $request->input('employee_id');
		$task->assignedEmployees()->sync($employees);

		return response()->json(['success' => __('Data Added successfully.')]);
	}
}
