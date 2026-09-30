<?php

namespace App\Services;

use App\Models\ExternalService;
use App\Models\NotificationSetting;
use App\Notifications\SendNotification;
use App\ViewModels\ISmsModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function dispatch(string $event, array $data): void
    {
        $setting = NotificationSetting::where('event', $event)->first();
        if (!$setting) {
            return;
        }

        $recipients = $this->normalizeRecipients($setting->recipients);

        if ($setting->notify_in_app) {
            $this->runChannel($event, 'in_app', function () use ($setting, $recipients, $data) {
                $this->sendInApp($setting, $recipients, $data);
            });
        }

        if ($setting->notify_whatsapp) {
            $this->runChannel($event, 'whatsapp', function () use ($setting, $recipients, $data) {
                $this->sendWhatsApp($setting, $recipients, $data);
            });
        }

        if ($setting->notify_sms) {
            $this->runChannel($event, 'sms', function () use ($setting, $recipients, $data) {
                $this->sendSMS($setting, $recipients, $data);
            });
        }

        if ($setting->notify_mail) {
            $this->runChannel($event, 'mail', function () use ($setting, $recipients, $data) {
                $this->sendEmail($setting, $recipients, $data);
            });
        }
    }

    private function normalizeRecipients($value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_filter(array_map('strval', $value))));
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_filter(array_map('strval', $decoded))));
        }

        // Backward compatibility for legacy rows whose recipients value is simply
        // "admin" or a comma-separated string rather than JSON.
        return array_values(array_unique(array_filter(array_map(
            static fn ($recipient) => trim((string) $recipient),
            explode(',', $value)
        ))));
    }

    private function runChannel(string $event, string $channel, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            // Each channel is isolated. One provider failure must not suppress the
            // remaining enabled channels or fail the business transaction that
            // already completed before notification dispatch.
            Log::error('Notification channel failed.', [
                'event' => $event,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendInApp(NotificationSetting $setting, array $recipients, array $data): void
    {
        if (!in_array('admin', $recipients, true) || empty($data['admin_users'])) {
            return;
        }

        $rawMessage = $setting->whatsapp_message
            ?? $setting->sms_message
            ?? ('Event: ' . $setting->event);
        $message = $this->replacePlaceholders($rawMessage, $data);

        $payload = [
            'sender_id'     => auth()->id() ?? null,
            'reminder_date' => date('Y-m-d'),
            'document_name' => $data['reference'] ?? null,
            'message'       => $message,
        ];

        Notification::send($data['admin_users'], new SendNotification($payload));
    }

    private function sendWhatsApp(NotificationSetting $setting, array $recipients, array $data): void
    {
        if (empty($setting->whatsapp_message)) {
            return;
        }

        $message = $this->replacePlaceholders($setting->whatsapp_message, $data);

        if (in_array('customer', $recipients, true) && !empty($data['customer_wa'])) {
            $this->triggerWhatsAppApi($data['customer_wa'], $message);
        }

        if (in_array('supplier', $recipients, true) && !empty($data['supplier_wa'])) {
            $this->triggerWhatsAppApi($data['supplier_wa'], $message);
        }

        if (in_array('admin', $recipients, true) && !empty($data['admin_users'])) {
            foreach ($data['admin_users'] as $admin) {
                if (!empty($admin->phone)) {
                    $this->triggerWhatsAppApi($admin->phone, $message);
                }
            }
        }
    }

    private function sendSMS(NotificationSetting $setting, array $recipients, array $data): void
    {
        if (empty($setting->sms_message)) {
            return;
        }

        $message = $this->replacePlaceholders($setting->sms_message, $data);

        if (in_array('customer', $recipients, true) && !empty($data['customer_phone'])) {
            $this->triggerSmsGateway($data['customer_phone'], $message);
        }

        if (in_array('supplier', $recipients, true) && !empty($data['supplier_phone'])) {
            $this->triggerSmsGateway($data['supplier_phone'], $message);
        }

        if (in_array('admin', $recipients, true) && !empty($data['admin_users'])) {
            foreach ($data['admin_users'] as $admin) {
                if (!empty($admin->phone)) {
                    $this->triggerSmsGateway($admin->phone, $message);
                }
            }
        }
    }

    private function sendEmail(NotificationSetting $setting, array $recipients, array $data): void
    {
        if (empty($setting->mail_message)) {
            return;
        }

        $messageBody = $this->replacePlaceholders($setting->mail_message, $data);
        $subject = ucwords(str_replace('_', ' ', $setting->event))
            . ' - '
            . ($data['reference'] ?? 'Update');

        if (in_array('customer', $recipients, true) && !empty($data['customer_email'])) {
            $this->triggerMailDriver($data['customer_email'], $subject, $messageBody);
        }

        if (in_array('supplier', $recipients, true) && !empty($data['supplier_email'])) {
            $this->triggerMailDriver($data['supplier_email'], $subject, $messageBody);
        }

        if (in_array('admin', $recipients, true) && !empty($data['admin_users'])) {
            foreach ($data['admin_users'] as $admin) {
                if (!empty($admin->email)) {
                    $this->triggerMailDriver($admin->email, $subject, $messageBody);
                }
            }
        }
    }

    private function triggerWhatsAppApi(string $phone, string $message): void
    {
        try {
            $whatsapp = \App\Models\WhatsappSetting::latest()->first();
            if (!$whatsapp) {
                Log::warning('WhatsApp notification is enabled but WhatsApp settings are unavailable.', [
                    'phone' => $phone,
                ]);
                return;
            }

            $whatsapp->sendMessage([$phone], 'text', $message);
        } catch (\Throwable $e) {
            // Isolate individual recipients as well as channels. A bad destination
            // must not prevent the remaining configured recipients from being tried.
            Log::error('WhatsApp notification delivery failed.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function triggerSmsGateway(string $phone, string $message): void
    {
        try {
            $smsProvider = ExternalService::where('active', true)
                ->where('type', 'sms')
                ->first();

            if (!$smsProvider) {
                Log::warning('SMS notification is enabled but no active SMS provider is configured.', [
                    'phone' => $phone,
                ]);
                return;
            }

            // SalePro's existing SMS path is the ISmsModel abstraction used by the
            // controller/manual SMS workflow. Reuse it instead of depending on a
            // separate App\Services\SmsService class that may not exist.
            if (app()->bound(ISmsModel::class)) {
                app(ISmsModel::class)->initialize([
                    'type' => 'onsite',
                    'mobile' => $phone,
                    'message' => $message,
                ]);
                return;
            }

            // Retain compatibility for installations that do provide the older
            // optional SmsService implementation.
            if (class_exists(\App\Services\SmsService::class)) {
                app(\App\Services\SmsService::class)->send($phone, $message);
                return;
            }

            Log::warning('SMS provider is configured but no SalePro SMS dispatcher is bound.', [
                'phone' => $phone,
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS notification delivery failed.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function triggerMailDriver(string $email, string $subject, string $body): void
    {
        try {
            // Notification templates stored in notification_settings are HTML (for
            // example they contain <br> and <strong>), so send them as HTML rather
            // than exposing those tags literally with Mail::raw().
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('Email notification delivery failed.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function replacePlaceholders(string $template, array $data): string
    {
        $placeholders = [
            '[customer]'  => $data['customer_name'] ?? '',
            '[reference]' => $data['reference'] ?? '',
            '[amount]'    => $data['amount'] ?? '',
            '[product]'   => $data['product'] ?? '',
            '[qty]'       => $data['qty'] ?? '',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
}
