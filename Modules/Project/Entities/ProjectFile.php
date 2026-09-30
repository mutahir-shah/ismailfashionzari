<?php

namespace Modules\Project\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
	protected $fillable = [
		'file_title','user_id','file_attachment','file_description','project_id'
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
