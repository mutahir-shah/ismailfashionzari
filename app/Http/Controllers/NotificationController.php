<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SendNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class NotificationController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if ($role->hasPermissionTo('all_notification')) {
            $lims_notification_all = DB::table('notifications')->get();
            return view('backend.notification.index', compact('lims_notification_all'));
        } else
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
    }
    public function store(Request $request)
    {
        $document = $request->document;
        if ($document) {
            $v = Validator::make(
                [
                    'extension' => strtolower($document->getClientOriginalExtension()),
                ],
                [
                    'extension' => 'in:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt',
                ]
            );
            if ($v->fails())
                return redirect()->back()->withErrors($v->errors());

            $documentName = date('Ymdhis') . '.' . $document->getClientOriginalExtension();
            $document->move(public_path('documents/notification'), $documentName);
            $request->document_name = $documentName;
        }
        $user = User::find($request->receiver_id);
        $user->notify(new SendNotification($request));
        return redirect()->back()->with('message', __('db.Notification send successfully'));
    }

    public function markAsRead()
    {
        Auth::user()->unreadNotifications->where('data.reminder_date', date('Y-m-d'))->markAsRead();
    }

    public function settings()
    {
        $settings = NotificationSetting::all();
        return view('backend.notification.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->input('settings', []);

        foreach ($data as $id => $values) {
            $setting = NotificationSetting::find($id);
            if ($setting) {
                $setting->update([
                    'notify_in_app'   => isset($values['notify_in_app']),
                    'notify_whatsapp' => isset($values['notify_whatsapp']),
                    'notify_sms'      => isset($values['notify_sms']),
                    'notify_mail'     => isset($values['notify_mail']),
                    'recipients'      => $values['recipients'] ?? [], // 👈 Saves selected array elements cleanly (fallback to empty array)
                    'whatsapp_message' => $values['whatsapp_message'] ?? null,
                    'sms_message'     => $values['sms_message'] ?? null,
                    'mail_message'    => $values['mail_message'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('message', 'Notification settings updated successfully!');
    }
}
