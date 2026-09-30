<?php

namespace Modules\Project\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
	protected $fillable = [
		'task_name','project_id','start_date','end_date','task_hour','description',
		'task_status','task_note','is_notify','added_by','task_progress'
	];

	public function project(){
		return $this->hasOne('Modules\Project\Entities\Project','id','project_id');
	}
	public function addedBy(){
		return $this->hasOne('App\Models\User','id','added_by');
	}

	public function assignedEmployees(){
		return $this->belongsToMany(\App\Models\Employee::class, 'employee_task', 'task_id', 'employee_id');
	}

	public function setStartDateAttribute($value)
	{
		try {
			$this->attributes['start_date'] = \Carbon\Carbon::createFromFormat(getDateFormat(), $value)->format('Y-m-d');
		} catch (\Exception $e) {
			$this->attributes['start_date'] = \Carbon\Carbon::parse($value)->format('Y-m-d');
		}
	}

	public function getStartDateAttribute($value)
	{
		return Carbon::parse($value)->format(getDateFormat());
	}

	public function setEndDateAttribute($value)
	{
		try {
			$this->attributes['end_date'] = \Carbon\Carbon::createFromFormat(getDateFormat(), $value)->format('Y-m-d');
		} catch (\Exception $e) {
			$this->attributes['end_date'] = \Carbon\Carbon::parse($value)->format('Y-m-d');
		}
	}

	public function getEndDateAttribute($value)
	{
		return Carbon::parse($value)->format(getDateFormat());
	}
}
