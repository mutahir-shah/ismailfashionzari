<?php

namespace App\SMSProviders;

use Illuminate\Support\Facades\Http;

class ZirconSms
{
    public function send($data)
    {
        $details = json_decode($data['details'], true);

        $response = Http::get('https://sender.zirconhost.com/api/v2/send.php', [
            'user_id'   => $details['user_id'],
            'api_key'   => $details['api_key'],
            'sender_id' => $details['sender_id'],
            'to'        => $data['recipent'],
            'message'   => $data['message'],
        ]);

        return $response->json();
    }
}
