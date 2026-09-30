<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\TaxReportService;
use App\Services\WarehouseAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxReportController extends Controller
{
    public function __construct(
        protected TaxReportService $taxReportService,
        protected WarehouseAccessService $warehouseAccess
    ) {}

    /**
     * Check permission for tax report.
     */
    protected function authorizeReport(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Role 1 (Admin) and Role 2 (Owner) always have access
        if ((int)$user->role_id <= 2) {
            return;
        }

        $role = Role::find($user->role_id);
        if (!$role || !$role->hasPermissionTo('tax-report')) {
            abort(403, __('db.Sorry! You are not allowed to access this module'));
        }
    }

    /**
     * Display the Consolidated Tax Report view.
     */
    public function index(Request $request)
    {
        $this->authorizeReport();

        $user = Auth::user();

        // Warehouse list
        if ($this->warehouseAccess->isRestricted()) {
            $lims_warehouse_list = Warehouse::where('id', $this->warehouseAccess->warehouseId())
                ->where('is_active', true)
                ->get();
            $warehouse_id = $this->warehouseAccess->warehouseId();
        } else {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            $warehouse_id = (int)$request->input('warehouse_id', 0);
        }

        $lims_customer_list = Customer::where('is_active', true)->select('id', 'name', 'company_name', 'tax_no')->get();
        $lims_supplier_list = Supplier::where('is_active', true)->select('id', 'name', 'company_name', 'vat_number')->get();

        if ($request->starting_date) {
            $starting_date = $request->starting_date;
            $ending_date = $request->ending_date;
        } else {
            $starting_date = date('Y-m-01');
            $ending_date = date('Y-m-d');
        }

        $all_permission = [];
        if ($user && $user->role_id) {
            $role = Role::find($user->role_id);
            if ($role) {
                $all_permission = $role->permissions->pluck('name')->toArray();
            }
        }

        return view('backend.report.tax_report', compact(
            'lims_warehouse_list',
            'lims_customer_list',
            'lims_supplier_list',
            'starting_date',
            'ending_date',
            'warehouse_id',
            'all_permission'
        ));
    }

    /**
     * DataTables server-side feed for Output Tax (Sales & Returns).
     */
    public function outputData(Request $request): JsonResponse
    {
        $this->authorizeReport();

        $filters = $this->extractFilters($request);
        $allTransactions = $this->taxReportService->getOutputTaxTransactions($filters);

        return $this->buildDataTablesResponse($request, $allTransactions);
    }

    /**
     * DataTables server-side feed for Input Tax (Purchases & Returns).
     */
    public function inputData(Request $request): JsonResponse
    {
        $this->authorizeReport();

        $filters = $this->extractFilters($request);
        $allTransactions = $this->taxReportService->getInputTaxTransactions($filters);

        return $this->buildDataTablesResponse($request, $allTransactions);
    }

    /**
     * DataTables server-side feed for Expense Tax.
     */
    public function expenseData(Request $request): JsonResponse
    {
        $this->authorizeReport();

        $filters = $this->extractFilters($request);
        $allTransactions = $this->taxReportService->getExpenseTaxTransactions($filters);

        return $this->buildDataTablesResponse($request, $allTransactions);
    }

    /**
     * AJAX endpoint for Consolidated Summary cards & Net Tax Position.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorizeReport();

        $filters = $this->extractFilters($request);
        $summary = $this->taxReportService->getConsolidatedSummary($filters);

        return response()->json($summary);
    }

    /**
     * Server-side full export for authorized filtered dataset.
     */
    public function export(Request $request)
    {
        $this->authorizeReport();

        $type = $request->input('type', 'output'); // output, input, expense
        $format = $request->input('format', 'csv'); // csv, excel, pdf
        $filters = $this->extractFilters($request);

        if ($type === 'input') {
            $data = $this->taxReportService->getInputTaxTransactions($filters);
            $totals = $this->taxReportService->getInputTaxTotals($filters);
            $title = 'Input_Tax_Report_' . date('Ymd_His');
            $reportName = __('db.Input Tax (Purchases)');
        } elseif ($type === 'expense') {
            $data = $this->taxReportService->getExpenseTaxTransactions($filters);
            $totals = $this->taxReportService->getExpenseTaxTotals($filters);
            $title = 'Expense_Tax_Report_' . date('Ymd_His');
            $reportName = __('db.Expense Tax');
        } else {
            $data = $this->taxReportService->getOutputTaxTransactions($filters);
            $totals = $this->taxReportService->getOutputTaxTotals($filters);
            $title = 'Output_Tax_Report_' . date('Ymd_His');
            $reportName = __('db.Output Tax (Sales)');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('backend.report.tax_report_pdf', compact('data', 'totals', 'title', 'reportName', 'filters', 'type'));
            return $pdf->download($title . '.pdf');
        }

        // CSV / Excel Stream export
        return $this->streamCsvExport($data, $totals, $title, $type);
    }

    /**
     * Print View for the full filtered dataset.
     */
    public function printView(Request $request)
    {
        $this->authorizeReport();

        $type = $request->input('type', 'output');
        $filters = $this->extractFilters($request);

        if ($type === 'input') {
            $data = $this->taxReportService->getInputTaxTransactions($filters);
            $totals = $this->taxReportService->getInputTaxTotals($filters);
            $reportName = __('db.Input Tax (Purchases)');
        } elseif ($type === 'expense') {
            $data = $this->taxReportService->getExpenseTaxTransactions($filters);
            $totals = $this->taxReportService->getExpenseTaxTotals($filters);
            $reportName = __('db.Expense Tax');
        } else {
            $data = $this->taxReportService->getOutputTaxTransactions($filters);
            $totals = $this->taxReportService->getOutputTaxTotals($filters);
            $reportName = __('db.Output Tax (Sales)');
        }

        return view('backend.report.tax_report_print', compact('data', 'totals', 'reportName', 'filters', 'type'));
    }

    protected function extractFilters(Request $request): array
    {
        return [
            'starting_date' => $request->input('starting_date'),
            'ending_date' => $request->input('ending_date'),
            'warehouse_id' => $request->input('warehouse_id'),
            'customer_id' => $request->input('customer_id'),
            'supplier_id' => $request->input('supplier_id'),
            'contact_id' => $request->input('contact_id'),
            'expense_category_id' => $request->input('expense_category_id'),
        ];
    }

    protected function buildDataTablesResponse(Request $request, array $allTransactions): JsonResponse
    {
        $searchValue = $request->input('search.value');
        $filtered = $allTransactions;

        // In-memory filter on normalized dataset if search term present
        if (!empty($searchValue)) {
            $s = strtolower(trim($searchValue));
            $filtered = array_values(array_filter($allTransactions, function ($item) use ($s) {
                return str_contains(strtolower($item['reference'] ?? ''), $s)
                    || str_contains(strtolower($item['contact_name'] ?? ''), $s)
                    || str_contains(strtolower($item['tax_number'] ?? ''), $s)
                    || str_contains(strtolower($item['warehouse_name'] ?? ''), $s)
                    || str_contains(strtolower($item['tax_name_rate'] ?? ''), $s)
                    || str_contains(strtolower($item['date'] ?? ''), $s);
            }));
        }

        // Calculate authoritative totals on the entire filtered dataset
        $totalTaxable = 0.0;
        $totalDiscount = 0.0;
        $totalTax = 0.0;
        $totalGrand = 0.0;

        foreach ($filtered as $row) {
            $totalTaxable += (float) ($row['taxable_amount'] ?? 0);
            $totalDiscount += (float) ($row['discount'] ?? 0);
            $totalTax += (float) ($row['tax_amount'] ?? 0);
            $totalGrand += (float) ($row['total_amount'] ?? 0);
        }

        $recordsTotal = count($allTransactions);
        $recordsFiltered = count($filtered);

        // Sorting
        $orderColumnIdx = $request->input('order.0.column', 1);
        $orderDir = strtolower($request->input('order.0.dir', 'desc'));
        
        $columnMap = [
            0 => 'type_badge',
            1 => 'raw_date',
            2 => 'reference',
            3 => 'contact_name',
            4 => 'tax_number',
            5 => 'warehouse_name',
            6 => 'taxable_amount',
            7 => 'discount',
            8 => 'tax_name_rate',
            9 => 'tax_amount',
            10 => 'total_amount',
            11 => 'payment_method',
        ];

        $sortKey = $columnMap[$orderColumnIdx] ?? 'raw_date';

        usort($filtered, function ($a, $b) use ($sortKey, $orderDir) {
            $valA = $a[$sortKey] ?? '';
            $valB = $b[$sortKey] ?? '';

            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = $valA <=> $valB;
            } else {
                $cmp = strcmp((string)$valA, (string)$valB);
            }

            return $orderDir === 'asc' ? $cmp : -$cmp;
        });

        // Pagination
        $start = (int)$request->input('start', 0);
        $length = (int)$request->input('length', 10);
        $pageData = ($length > 0) ? array_slice($filtered, $start, $length) : $filtered;

        $decimal = config('decimal') ?: 2;

        // Format numbers for display
        $formattedData = array_map(function ($row, $idx) use ($start, $decimal) {
            return [
                'index' => $start + $idx + 1,
                'type_badge' => $row['type_badge'],
                'date' => $row['date'],
                'reference' => $row['reference'],
                'contact_name' => $row['contact_name'],
                'tax_number' => $row['tax_number'],
                'warehouse_name' => $row['warehouse_name'],
                'taxable_amount' => number_format((float)$row['taxable_amount'], $decimal),
                'discount' => number_format((float)$row['discount'], $decimal),
                'tax_name_rate' => $row['tax_name_rate'],
                'tax_amount' => number_format((float)$row['tax_amount'], $decimal),
                'total_amount' => number_format((float)$row['total_amount'], $decimal),
                'payment_method' => $row['payment_method'],
            ];
        }, $pageData, array_keys($pageData));

        return response()->json([
            'draw' => (int)$request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData,
            'totals' => [
                'taxable_amount' => number_format($totalTaxable, $decimal),
                'discount' => number_format($totalDiscount, $decimal),
                'tax_amount' => number_format($totalTax, $decimal),
                'total_amount' => number_format($totalGrand, $decimal),
                'raw_taxable' => $totalTaxable,
                'raw_discount' => $totalDiscount,
                'raw_tax' => $totalTax,
                'raw_total' => $totalGrand,
            ],
        ]);
    }

    protected function streamCsvExport(array $data, array $totals, string $filename, string $type): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data, $totals, $type) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            if ($type === 'expense') {
                fputcsv($handle, [
                    '#',
                    __('db.Type'),
                    __('db.date'),
                    __('db.reference'),
                    __('db.category'),
                    __('db.Warehouse'),
                    __('db.Net Amount Excl. Tax'),
                    __('db.Tax Name / Rate'),
                    __('db.Tax Amount'),
                    __('db.Total Amount'),
                    __('db.Payment Method')
                ]);

                foreach ($data as $idx => $row) {
                    fputcsv($handle, [
                        $idx + 1,
                        $row['is_return'] ? 'Return' : 'Expense',
                        $row['date'],
                        $row['reference'],
                        $row['category_name'] ?? $row['contact_name'],
                        $row['warehouse_name'],
                        $row['taxable_amount'],
                        $row['tax_name_rate'],
                        $row['tax_amount'],
                        $row['total_amount'],
                        $row['payment_method'],
                    ]);
                }

                // Totals row
                fputcsv($handle, [
                    '',
                    'TOTAL',
                    '',
                    '',
                    '',
                    '',
                    $totals['taxable_amount'],
                    '',
                    $totals['tax_amount'],
                    $totals['total_amount'],
                    '',
                ]);
            } else {
                fputcsv($handle, [
                    '#',
                    __('db.Type'),
                    __('db.date'),
                    __('db.reference'),
                    $type === 'input' ? __('db.Supplier') : __('db.customer'),
                    __('db.Tax Number'),
                    __('db.Warehouse'),
                    __('db.Net Amount Excl. Tax'),
                    __('db.Discount'),
                    __('db.Tax Name / Rate'),
                    __('db.Tax Amount'),
                    __('db.Total Amount'),
                    __('db.Payment Status')
                ]);

                foreach ($data as $idx => $row) {
                    fputcsv($handle, [
                        $idx + 1,
                        $row['is_return'] ? 'Return' : ($type === 'input' ? 'Purchase' : 'Sale'),
                        $row['date'],
                        $row['reference'],
                        $row['contact_name'],
                        $row['tax_number'],
                        $row['warehouse_name'],
                        $row['taxable_amount'],
                        $row['discount'],
                        $row['tax_name_rate'],
                        $row['tax_amount'],
                        $row['total_amount'],
                        $row['payment_method'],
                    ]);
                }

                // Totals row
                fputcsv($handle, [
                    '',
                    'TOTAL',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $totals['taxable_amount'],
                    $totals['discount'] ?? 0,
                    '',
                    $totals['tax_amount'],
                    $totals['total_amount'],
                    '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
