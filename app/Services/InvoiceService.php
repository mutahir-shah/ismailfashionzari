<?php

namespace App\Services;

use App\Models\InvoiceSchema;
use App\Models\InvoiceSetting;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceName(string $default, bool $useInvoiceSequence = false)
    {
        $invoice_settings = InvoiceSetting::active_setting();

        if (!$useInvoiceSequence || !$this->invoiceGenerationSettingsEnabled($invoice_settings)) {
            return $default . date("Ymd") . '-' . date("his");
        }

        $prefix = $invoice_settings->prefix ?: $default;

        if ($invoice_settings->numbering_type == "sequential") {
            return DB::transaction(function () use ($invoice_settings, $prefix) {
                $invoice_schema = InvoiceSchema::query()->lockForUpdate()->latest('id')->first();

                if ($invoice_schema == null) {
                    $next_invoice_number = (int) $invoice_settings->start_number;
                    InvoiceSchema::query()->create(['last_invoice_number' => $next_invoice_number]);
                } else {
                    $next_invoice_number = ((int) $invoice_schema->last_invoice_number) + 1;
                    $invoice_schema->update(['last_invoice_number' => $next_invoice_number]);
                }

                return $prefix . '-' . $next_invoice_number;
            });
        } elseif ($invoice_settings->numbering_type == "random") {
            return $prefix . '-' . rand($invoice_settings->start_number, str_repeat('9', (int)$invoice_settings->number_of_digit));
        } else {
            return  $prefix . date("Ymd") . '-' . date("his");
        }
    }

    private function invoiceGenerationSettingsEnabled($invoice_settings): bool
    {
        if ($invoice_settings == null) {
            return false;
        }

        $show_active_status = json_decode($invoice_settings->show_column ?? '{}');

        return isset($show_active_status->active_generat_settings)
            && (int) $show_active_status->active_generat_settings == 1;
    }
}
