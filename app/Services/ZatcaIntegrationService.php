<?php

namespace App\Services;

use App\Models\Returns;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Facades\Module;
use RuntimeException;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

/**
 * Stable core bridge for the optional ZATCA module.
 *
 * Core SalePro code must never resolve a Modules\ZatcaIntegrationKsa class
 * unless the module physically exists and is enabled.
 */
class ZatcaIntegrationService
{
    private const MODULE = 'ZatcaIntegrationKsa';

    public function moduleIsAvailable(): bool
    {
        if (! is_dir(base_path('Modules/'.self::MODULE))) {
            return false;
        }

        try {
            $module = Module::find(self::MODULE);

            return $module !== null && $module->isEnabled();
        } catch (\Throwable) {
            return false;
        }
    }

    public function processSale(Sale $sale): void
    {
        $this->process('createAndSubmitSale', $sale);
    }

    public function processReturn(Returns $return): void
    {
        $this->process('createAndSubmitReturn', $return);
    }

    public function isSourceLocked(string $sourceType, int $sourceId): bool
    {
        // Keep fiscal records immutable even if the optional module is later
        // disabled or its directory is accidentally removed.
        return Schema::hasTable('zatca_ksa_documents')
            && DB::table('zatca_ksa_documents')
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->exists();
    }

    public function qrForSale(Sale $sale): string
    {
        $settings = gen_setting();
        $mode = $settings->zatca_mode
            ?? (($settings->is_zatca ?? false) ? 'phase1' : 'disabled');

        if ($mode === 'phase2') {
            $resolver = 'Modules\\ZatcaIntegrationKsa\\Services\\ZatcaQrResolver';
            if (! $this->moduleIsAvailable() || ! class_exists($resolver)) {
                throw new RuntimeException('ZATCA Phase 2 is selected, but the ZatcaIntegrationKsa module is unavailable. Restore and enable the module before printing this fiscal invoice.');
            }

            return app($resolver)->forSale($sale);
        }

        if ($mode !== 'phase1') {
            return (string) $sale->reference_no;
        }

        return GenerateQrCode::fromArray([
            new Seller((string) ($settings->company_name ?? config('company_name'))),
            new TaxNumber((string) ($settings->vat_registration_number ?? config('vat_registration_number'))),
            new InvoiceDate($sale->created_at->format('Y-m-d\TH:i:s')),
            new InvoiceTotalAmount(number_format((float) $sale->grand_total, 4, '.', '')),
            new InvoiceTaxAmount(number_format((float) ($sale->total_tax + $sale->order_tax), 4, '.', '')),
        ])->toBase64();
    }

    private function process(string $method, Sale|Returns $source): void
    {
        if (! $this->moduleIsAvailable()) {
            return;
        }

        $service = 'Modules\\ZatcaIntegrationKsa\\Services\\ZatcaDocumentService';
        if (! class_exists($service)) {
            return;
        }

        app($service)->{$method}($source);
    }
}
