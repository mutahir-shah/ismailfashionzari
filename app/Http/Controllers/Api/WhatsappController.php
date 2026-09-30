<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\DB;
use Auth;

class WhatsappController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function settings()
    {
        $settings = WhatsappSetting::firstOrCreate([]);

        $formSchema = [
            'title' => 'WhatsApp Settings',
            'submit_url' => '/whatsapp/settings',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'phone_number_id',
                    'label' => 'Phone Number ID',
                    'value' => $settings->phone_number_id
                ],
                [
                    'type' => 'text',
                    'name' => 'business_account_id',
                    'label' => 'WhatsApp Business Account ID',
                    'value' => $settings->business_account_id
                ],
                [
                    'type' => 'text',
                    'name' => 'permanent_access_token',
                    'label' => 'Permanent Access Token',
                    'value' => $settings->permanent_access_token
                ],
                [
                    'type' => 'tags',
                    'name' => 'message_types',
                    'label' => 'Message Types (notification triggers)',
                    'value' => $settings->message_types ? explode(',', $settings->message_types) : []
                ],
            ]
        ];

        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'phone_number_id' => 'nullable|string',
            'business_account_id' => 'nullable|string',
            'permanent_access_token' => 'nullable|string',
            'message_types' => 'nullable|array',
        ]);

        $settings = WhatsappSetting::first();
        $settings->update([
            'phone_number_id' => $data['phone_number_id'],
            'business_account_id' => $data['business_account_id'],
            'permanent_access_token' => $data['permanent_access_token'],
            'message_types' => isset($data['message_types']) ? implode(',', $data['message_types']) : null,
        ]);

        return response()->json(['success' => true, 'message' => trans('file.Data updated successfully')]);
    }

    public function templates()
    {
        $settings = WhatsappSetting::first();
        if (!$settings || empty($settings->business_account_id) || empty($settings->permanent_access_token)) {
            return response()->json(['message' => trans('file.whatsapp_credentials_missing')], 403);
        }

        $result = $settings->getTemplates();

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 500);
        }

        $templates = $result['data'] ?? [];

        $rows = [];
        foreach ($templates as $template) {
            $rows[] = [
                'name' => $template['name'],
                'language' => $template['language'],
                'status' => $template['status'],
                'category' => $template['category'],
                'id' => $template['id']
            ];
        }

        return $this->withDashBackground([
            'title' => 'Message Templates',
            'columns' => [
                ['label' => 'Name', 'field' => 'name', 'type' => 'text'],
                ['label' => 'Language', 'field' => 'language', 'type' => 'text'],
                ['label' => 'Status', 'field' => 'status', 'type' => 'text'],
                ['label' => 'Category', 'field' => 'category', 'type' => 'text'],
                ['label' => 'Action', 'type' => 'action', 'action' => [
                    'type' => 'custom_api',
                    'api_url' => '/whatsapp/templates/{name}',
                    'method' => 'DELETE',
                    'icon' => 'delete'
                ]],
            ],
            'rows' => $rows
        ], 'app');
    }

    public function deleteTemplate($name)
    {
        $settings = WhatsappSetting::first();
        $result = $settings->deleteTemplate($name);
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 500);
    }

    public function sendPage()
    {
        $settings = WhatsappSetting::first();
        if (!$settings || empty($settings->business_account_id) || empty($settings->permanent_access_token)) {
            return response()->json(['message' => trans('file.whatsapp_credentials_missing')], 403);
        }

        $templatesResult = $settings->getTemplates();
        $templates = [];
        if (!isset($templatesResult['error'])) {
            $templatesRaw = $templatesResult['data'] ?? [];
            foreach ($templatesRaw as $t) {
                $templates[] = ['label' => $t['name'] . ' (' . $t['language'] . ')', 'value' => $t['name'] . '|' . $t['language']];
            }
        }

        $suppliers = DB::table('suppliers')->whereNotNull('wa_number')->select('name', 'wa_number as phone')->get();
        $customers = DB::table('customers')->whereNotNull('wa_number')->select('name', 'wa_number as phone')->get();

        $receivers = [];
        foreach ($suppliers as $s) $receivers[] = ['label' => $s->name . ' (Supplier)', 'value' => $s->phone];
        foreach ($customers as $c) $receivers[] = ['label' => $c->name . ' (Customer)', 'value' => $c->phone];


        $formSchema = [
            'title' => 'Send WhatsApp Message',
            'submit_url' => '/whatsapp/send',
            'method' => 'POST',
            'fields' => [
                [
                    'type' => 'select',
                    'name' => 'receiver_phone[]', // Array notation for multiple
                    'label' => 'Receivers',
                    'options' => $receivers,
                    'multiple' => true,
                ],
                [
                    'type' => 'select',
                    'name' => 'template_info',
                    'label' => 'Select Template',
                    'options' => $templates,
                    'info' => 'Select a template to send. If selected, custom text is ignored.'
                ],
                [
                    'type' => 'textarea',
                    'name' => 'message',
                    'label' => 'Custom Message',
                ],
                [
                    'type' => 'file',
                    'name' => 'attachment',
                    'label' => 'Attachment',
                ],
                [
                    'type' => 'select',
                    'name' => 'attachment_type',
                    'label' => 'Attachment Type',
                    'options' => [
                        ['label' => 'Image', 'value' => 'image'],
                        ['label' => 'Document', 'value' => 'document']
                    ],
                ]
            ]
        ];
        return response()->json($this->withDashBackground($formSchema, 'app'));
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'receiver_phone' => 'required|array',
            'receiver_phone.*' => 'required',
            'template_info' => 'nullable',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
            'attachment_type' => 'nullable|in:image,document',
        ]);

        $phoneNumbers = $data['receiver_phone'];
        $type = 'text';
        $messageContent = null;

        if (!empty($data['template_info'])) {
            $type = 'template';
            list($messageContent['name'], $messageContent['lang_code']) = explode('|', $data['template_info']);
        } else if ($request->hasFile('attachment')) {
            $type = $data['attachment_type'] === 'image' ? 'image' : 'document';
            $messageContent = [
                'file' => $request->file('attachment'),
                'caption' => $data['message'] ?? null,
            ];
        } else {
            $type = 'text';
            $messageContent = $data['message'] ?? null;
        }

        $settings = WhatsappSetting::first();
        $result = $settings->sendMessage($phoneNumbers, $type, $messageContent);

        if ($result['success'] ?? false) {
            return response()->json(['success' => true, 'message' => $result['message']]);
        } else {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed'], 500);
        }
    }
}
