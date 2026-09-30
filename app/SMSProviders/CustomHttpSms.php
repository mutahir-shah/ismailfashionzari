<?php 

namespace App\SMSProviders;

use Illuminate\Support\Facades\Http;

class CustomHttpSms
{
    public function send($data)
    {
        $details = is_string($data['details']) ? json_decode($data['details']) : $data['details'];
        
        $method = $details->method ?? 'POST';
        $apiUrl = $details->api_url ?? '';
        
        $headersStr = $details->headers ?? '{}';
        $headers = json_decode($headersStr, true) ?? [];
        
        $bodyTemplateStr = $details->body_template ?? '';
        
        $responses = [];
        
        foreach ($data['numbers'] as $number) {
            $number = trim($number);
            if (empty($number)) continue;
            
            // Replace placeholders
            $replacedUrl = $this->replacePlaceholders($apiUrl, $number, $data['message']);
            
            $replacedBodyStr = $this->replacePlaceholders($bodyTemplateStr, $number, $data['message']);
            
            $replacedHeaders = [];
            foreach ($headers as $key => $val) {
                $replacedHeaders[$key] = $this->replacePlaceholders($val, $number, $data['message']);
            }
            
            $request = Http::withHeaders($replacedHeaders);
            
            if (strtoupper($method) == 'GET') {
                $response = $request->get($replacedUrl);
            } else {
                // If it's POST and the body string looks like JSON, we parse it to array so Http::post sends as JSON.
                // If it fails to parse as JSON, we send as raw body.
                $bodyData = json_decode($replacedBodyStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($bodyData)) {
                    $response = $request->post($replacedUrl, $bodyData);
                } else {
                    $response = $request->withBody($replacedBodyStr, 'text/plain')->post($replacedUrl);
                }
            }
            
            $responses[] = $response->json() ?? $response->body();
        }
        
        return $responses;
    }
    
    private function replacePlaceholders($string, $phone, $message)
    {
        if (empty($string)) return '';
        
        $search = [
            '{phone}',
            '{message}',
        ];
        
        $replace = [
            $phone,
            $message,
        ];
        
        return str_replace($search, $replace, $string);
    }
}
