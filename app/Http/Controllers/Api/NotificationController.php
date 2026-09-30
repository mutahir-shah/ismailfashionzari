<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use App\Models\User;
use App\Notifications\SendNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Traits\TenantInfo;

class NotificationController extends Controller
{
    use TenantInfo;

    use ProvidesThemeBackgrounds;

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access notifications
            if (!$role->hasPermissionTo('all_notification')) {
                return new ErrorResource('Sorry! You are not allowed to access this module.');
            }

            // Get all notifications
            $notifications = DB::table('notifications')->orderBy('created_at', 'desc')->get();

            // Format notifications for datatable
            $notificationsTable = $notifications->map(function ($notification) {
                $data = json_decode($notification->data);
                $fromUser = DB::table('users')->select('name', 'email')->where('id', $data->sender_id ?? null)->first();
                $toUser = DB::table('users')->select('name', 'email')->where('id', $data->receiver_id ?? null)->first();

                return [
                    'id' => $notification->id,
                    'date' => date(config('date_format', 'Y-m-d'), strtotime($notification->created_at)),
                    'from' => $fromUser ? $fromUser->name . ' (' . $fromUser->email . ')' : 'N/A',
                    'to' => $toUser ? $toUser->name . ' (' . $toUser->email . ')' : 'N/A',
                    'document' => isset($data->document_name) && $data->document_name
                        ? "<a href='" . url('documents/notification/' . $data->document_name) . "' target='_blank'>" . htmlspecialchars($data->document_name) . "</a>"
                        : 'N/A',
                    'message' => $data->message ?? 'N/A',
                    'reminder_date' => isset($data->reminder_date)
                        ? date(config('date_format', 'Y-m-d'), strtotime($data->reminder_date))
                        : 'N/A',
                    'status' => $notification->read_at
                        ? "<span style='color: green; font-weight: bold;'>Read</span>"
                        : "<span style='color: red; font-weight: bold;'>Unread</span>",
                ];
            });

            return $this->withDashBackground([
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "All Notifications",
                "row_height" => 5,
                "add_url" => "/notifications/create",
                "columns" => [
                    [
                        "label" => "Date",
                        "field" => "date",
                        "type" => "text",
                    ],
                    [
                        "label" => "From",
                        "field" => "from",
                        "type" => "text",
                    ],
                    [
                        "label" => "To",
                        "field" => "to",
                        "type" => "text",
                    ],
                    [
                        "label" => "Document",
                        "field" => "document",
                        "type" => "html",
                    ],
                    [
                        "label" => "Message",
                        "field" => "message",
                        "type" => "text",
                    ],
                    [
                        "label" => "Reminder Date",
                        "field" => "reminder_date",
                        "type" => "text",
                    ],
                    [
                        "label" => "Status",
                        "field" => "status",
                        "type" => "html",
                    ],
                ],
                "rows" => $notificationsTable,
            ], 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to send notifications
            if (!$role->hasPermissionTo('send_notification')) {
                return new ErrorResource('Sorry! You are not allowed to send notifications.');
            }

            // Get users list for selection
            $users = User::where([
                ['is_active', true],
                ['id', '!=', Auth::id()]
            ])->get();

            $userOptions = $users->map(function ($user) {
                return [
                    'label' => $user->name . ' (' . $user->email . ')',
                    'value' => $user->id,
                ];
            })->values()->toArray();

            $formSchema = [
                "debug_bar" => env("APP_DEBUG", false) ? true : false,
                "title" => "Send Notification",
                "submit_url" => "/notifications",
                "method" => "POST",
                "navigate_url" => "/notifications",
                "fields" => [
                    [
                        "type" => "hidden",
                        "name" => "sender_id",
                        "value" => Auth::id(),
                    ],
                    [
                        "type" => "select",
                        "name" => "receiver_id",
                        "label" => "Select User",
                        "placeholder" => "Choose user to send notification",
                        "options" => $userOptions,
                        "info" => "Select the user who will receive this notification",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "datepicker",
                        "name" => "reminder_date",
                        "label" => "Reminder Date",
                        "placeholder" => "Select reminder date",
                        "format_specifier" => "dd/MM/yyyy",
                        "info" => "Optional: Set a reminder date for this notification",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "file",
                        "name" => "document",
                        "label" => "Attach Document",
                        "placeholder" => "Select document to attach",
                        "allowed_extensions" => ["jpg", "jpeg", "png", "gif", "pdf", "csv", "docx", "xlsx", "txt"],
                        "multiple" => false,
                        "info" => "Optional: Attach a document to this notification",
                        "show_info_icon" => true,
                    ],
                    [
                        "type" => "text",
                        "name" => "message",
                        "label" => "Message",
                        "placeholder" => "Enter your message here",
                        "multiline" => true,
                        "info" => "Enter the notification message content",
                        "show_info_icon" => true,
                    ],
                ],
            ];

            return $this->withDashBackground($formSchema, 'app');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the notification form.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created notification in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to send notifications
            if (!$role->hasPermissionTo('send_notification')) {
                return new ErrorResource('Sorry! You are not allowed to send notifications.');
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string|max:1000',
                'reminder_date' => 'nullable|date',
                'document' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,csv,docx,xlsx,txt|max:2048'
            ]);

            if ($validator->fails()) {
                return new ErrorResource($validator->errors()->first());
            }

            // Handle document upload
            $documentName = null;
            if ($request->hasFile('document')) {
                $document = $request->file('document');
                $documentName = date('Ymdhis') . '.' . $document->getClientOriginalExtension();

                if (config('database.connections.saleprosaas_landlord')) {
                    $documentName = $this->getTenantId() . '_' . $documentName;
                }

                $document->move(public_path('documents/notification'), $documentName);
                $request->merge(['document_name' => $documentName]);
            }

            // Send notification to user
            $receiver = User::find($request->receiver_id);
            $receiver->notify(new SendNotification($request));

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the notification.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark user notifications as read.
     */
    public function markAsRead()
    {
        try {
            Auth::user()->unreadNotifications->where('data.reminder_date', date('Y-m-d'))->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking notifications as read.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
