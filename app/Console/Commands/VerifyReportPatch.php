<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\ReportController;

class VerifyReportPatch extends Command
{
    protected $signature = 'test:verify_report_patch';
    protected $description = 'Verify ReportController patch';

    public function handle()
    {
        $this->info("Verifying ReportController patch...");
        
        $request = Request::create('/report/warehouse_sale_data', 'POST', [
            'start_date' => '2000-01-01',
            'end_date' => date('Y-m-d', strtotime('+1 day')),
            'warehouse_id' => \App\Models\Warehouse::first()->id,
            'length' => 10,
            'start' => 0,
            'search' => ['value' => ''],
            'order' => [['column' => 1, 'dir' => 'desc']]
        ]);

        $controller = app(ReportController::class);
        
        ob_start();
        $controller->warehouseSaleData($request);
        $json = ob_get_clean();
        
        $data = json_decode($json, true);
        
        if (isset($data['data']) && count($data['data']) > 0) {
            $this->info("Found " . count($data['data']) . " records in warehouseSaleData.");
            foreach ($data['data'] as $row) {
                // Remove HTML formatting if any
                $grandTotal = strip_tags($row['grand_total'] ?? '');
                $paid = strip_tags($row['paid'] ?? '');
                $due = strip_tags($row['due'] ?? '');
                
                $this->info("Ref: {$row['reference_no']} | Grand Total: {$grandTotal} | Paid: {$paid} | Due: {$due}");
            }
        } else {
            $this->error("No data returned or error: " . $json);
        }
    }
}
