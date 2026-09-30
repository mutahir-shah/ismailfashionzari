<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class WhatsappSetting extends Model
{
    protected $fillable = [
        'phone_number_id',
        'business_account_id',
        'permanent_access_token',
    ];

    private $baseUrl = "https://graph.facebook.com/v22.0";

    public function sendMessage(array $phoneNumbers, string $type, $messageContent)
    {
        // Set endpoint and headers
        $messageEndpoint = "{$this->baseUrl}/{$this->phone_number_id}/messages";
        $headers = [
            'Authorization' => "Bearer {$this->permanent_access_token}",
            'Accept' => 'application/json',
        ];

        try {
            // Base payload
            $payload = [
                'messaging_product' => 'whatsapp',
            ];

            // Template message
            if ($type == 'template') {
                $payload['type'] = 'template';
                $payload['template'] = [
                        'name' => $messageContent['name'],
                        'language' => ['code' => $messageContent['lang_code']],
                ];
            }
            // Text message
            elseif ($type == 'text') {
                $payload['type'] = 'text';
                $payload['text'] = ['body' => $messageContent];
            }
            // Image or Document
            elseif (in_array($type, ['image', 'document'])) {
                $file = $messageContent['file'];
                $originalName = $file->getClientOriginalName();

                // 🟢 Step 1: Upload media to WhatsApp
                $uploadResponse = Http::withOptions(['verify' => false])
                    ->withHeaders([
                        'Authorization' => "Bearer {$this->permanent_access_token}",
                    ])->attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $originalName
                    )->post("{$this->baseUrl}/{$this->phone_number_id}/media", [
                        'messaging_product' => 'whatsapp',
                    ]);

                if (!$uploadResponse->successful()) {
                    return [
                        'success' => false,
                        'message' => __('db.failed_upload_media_whatsApp'),
                        'body' => $uploadResponse->body(),
                    ];
                }

                $mediaId = $uploadResponse->json('id');
                if (!$mediaId) {
                    return [
                        'success' => false,
                        'message' => __('db.media_id_not_returned_from_WhatsApp'),
                    ];
                }

                $payload['type'] = $type;
                $payload[$type] = ['id' => $mediaId];

                if ($type == 'document') {
                    $payload[$type]['filename'] = $originalName;
                }

                if (!empty($messageContent['caption'])) {
                    $payload[$type]['caption'] = $messageContent['caption'];
                }
            }

            $results = [];// Store response per number for debug purpose

            // Loop through all numbers
            foreach ($phoneNumbers as $phoneNumber) {
                $payload['to'] = $phoneNumber;
                // Dispatch to queue for async send
                dispatch(function () use ($headers, $messageEndpoint, $payload, $phoneNumber, &$results) {
                    // Send message request
                    $response = Http::withOptions(['verify' => false])
                    ->withHeaders($headers)
                    ->post($messageEndpoint, $payload);

                    // Store response per number for debug purpose
                    $results[$phoneNumber] = $response->json();
                });
            }
            return [
                'success' => true,
                'message' => __('db.message_sent_successfully'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    // 🔹 Get templates from Facebook
    public function getTemplates()
    {
        $url = "{$this->baseUrl}/{$this->business_account_id}/message_templates";
        $headers = ['Authorization' => "Bearer {$this->permanent_access_token}"];

        $response = Http::withOptions(['verify' => false])->withHeaders($headers)->get($url);

        if ($response->successful()) {
            return $response->json('data');
        }

        return ['error' => __('db.failed_to_fetch_templates')];
    }

    // 🔹 Delete template from Facebook
    public function deleteTemplate(string $name)
    {
        $url = "{$this->baseUrl}/{$this->business_account_id}/message_templates?name={$name}";
        $headers = ['Authorization' => "Bearer {$this->permanent_access_token}"];

        $response = Http::withOptions(['verify' => false])->withHeaders($headers)->delete($url);

        if ($response->successful()) {
            return ['success' => true, 'message' => __('db.template_deleted_successfully')];
        }

        return [
            'success' => false,
            'message' => __('db.failed_delete_template'),
            'response' => $response->json(),
        ];
    }

}
