<?php

namespace App\Http\Controllers;

use Mail;
use App\Models\ActivityLog;
use App\Mail\UserLogMessage;
use App\Mail\AdminLogMessage;
use App\Traits\FileHandleTrait;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, FileHandleTrait;

    public function setSuccessMessage($message)
	{
    	session()->flash('message',$message);
    	session()->flash('type','success');
        return redirect()->back();
	}

	public function setErrorMessage($message)
	{
		session()->flash('not_permitted',$message);
		session()->flash('type','danger');
        return redirect()->back();
	}

	public function createActivityLog($log_data)
	{
		ActivityLog::create($log_data);
		if(isset($log_data['mail_setting']) && isset($log_data['admin_email'])) {
			$this->setMailInfo($log_data['mail_setting']);
			Mail::to($log_data['admin_email'])->send(new AdminLogMessage($log_data));
			if($log_data['user_email'])
				Mail::to($log_data['user_email'])->send(new UserLogMessage($log_data));
		}
	}
}
