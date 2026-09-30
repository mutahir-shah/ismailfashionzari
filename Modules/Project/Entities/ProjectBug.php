<?php

namespace Modules\Project\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProjectBug extends Model
{
	protected $fillable = [
		'title','user_id','bug_attachment','project_id','status'
	];

	public function project(){
		return $this->hasOne('Modules\Project\Entities\Project','id','project_id');
	}
	public function User(){
		return $this->hasOne('App\Models\User','id','user_id');
	}

	public function getCreatedAtAttribute($value)
	{
		return Carbon::parse($value)->format(getDateFormat().'--H:i');
	}
}
