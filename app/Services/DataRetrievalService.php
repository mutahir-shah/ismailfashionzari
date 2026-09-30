<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\UnitResource;
use App\Http\Resources\AccountResource;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Tax;
use App\Models\DiscountPlan;
use App\Models\HrmSetting;
use App\Models\PosSetting;
use App\Models\MailSetting;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Warehouse;
use DB;

class DataRetrievalService
{
    public function getAllUnits()
    {
        $role = Role::find(Auth::user()->role_id);

        if ($role->hasPermissionTo('unit')) {
            $units = Unit::where('is_active', true)->get();

            return UnitResource::collection($units);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to units.',
            ], 403);
        }
    }

    public function getAllBrands()
    {
        try {
            // Fetch all active brands
            $brands = Brand::where('is_active', true)->get();

            // Return success response with the list of brands
            return $brands;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve brands.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDiscountPlans()
    {
        try {
            $lims_discount_plan_list = DiscountPlan::where('is_active', true)->get();
            return $lims_discount_plan_list;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllWarehouses()
    {
        try {
            $lims_warehouse_list = Warehouse::where('is_active', true)->get();
            return $lims_warehouse_list;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllIncomeCategories()
    {
        try {
            $lims_income_category_list = IncomeCategory::where('is_active', true)->get();
            return $lims_income_category_list;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllExpenseCategories()
    {
        try {
            $lims_expense_category_list = ExpenseCategory::where('is_active', true)->get();
            return $lims_expense_category_list;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllAccounts()
    {
        try {
            $lims_account_list = app(PaymentAccountService::class)->validOperationalAccounts();
            return $lims_account_list;
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllCategories()
    {
        try {
            $user = Auth::user();
            $role = Role::find($user->role_id);

            // Check if the user has permission to access the category module
            if (!$role->hasPermissionTo('category')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry! You are not allowed to access this module.',
                ], 403);
            }


            // Retrieve categories
            $categories = Category::where('is_active', true)
                ->orderBy('id', 'desc')
                ->get();


            return $categories;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving category data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function hrmSetting()
    {
        try {
            $lims_hrm_setting_data = HrmSetting::latest()->first();
            return $lims_hrm_setting_data;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAccountList()
    {
        try {
            $lims_account_list = app(PaymentAccountService::class)->validOperationalAccounts();
            return AccountResource::collection($lims_account_list);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function posSetting()
    {
        try {
            $lims_pos_setting_data = PosSetting::latest()->first();

            if ($lims_pos_setting_data)
                $options = explode(',', $lims_pos_setting_data->payment_options);
            else
                $options = [];

            $lims_pos_setting_data['options'] = $options;

            return $lims_pos_setting_data;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function mailSetting()
    {
        try {
            $mail_setting_data = MailSetting::latest()->first();
            return $mail_setting_data;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentGateways()
    {
        try {
            $payment_gateways = DB::table('external_services')->where('type', 'payment')->get();
            return $payment_gateways;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductTypes()
    {
        $productTypes = [
            ["label" => "Standard", "value" => "standard"],
            ["label" => "Combo", "value" => "combo"],
            ["label" => "Digital", "value" => "digital"],
            ["label" => "Service", "value" => "service"],
        ];

        return $productTypes;
    }

    public function getBarcodeSymbologies()
    {
        $barcodeSymbologies = [
            ["label" => "Code 128", "value" => "C128"],
            ["label" => "Code 39", "value" => "C39"],
            ["label" => "UPC-A", "value" => "UPCA"],
            ["label" => "UPC-E", "value" => "UPCE"],
            ["label" => "EAN-8", "value" => "EAN8"],
            ["label" => "EAN-13", "value" => "EAN13"],
        ];

        return $barcodeSymbologies;
    }

    public function getTaxMethods()
    {
        $taxMethods = [
            ["label" => "Exclusive", "value" => "1"],
            ["label" => "Inclusive", "value" => "2"],
        ];

        return $taxMethods;
    }

    public function getPurchaseStatus()
    {
        $purchaseStatus = [
            ["label" => "Received", "value" => "1"],
            ["label" => "Partial", "value" => "2"],
            ["label" => "Pending", "value" => "3"],
            ["label" => "Ordered", "value" => "4"],
        ];

        return $purchaseStatus;
    }

    public function getSaleStatus()
    {
        $saleStatus = [
            ["label" => "Completed", "value" => "1"],
            ["label" => "Pending", "value" => "2"],
        ];

        return $saleStatus;
    }

    public function getSalePaymentStatus()
    {
        $salePaymentStatus = [
            ["label" => "Pending", "value" => "1"],
            ["label" => "Due", "value" => "2"],
            ["label" => "Partial", "value" => "3"],
            ["label" => "Paid", "value" => "4"],
        ];

        return $salePaymentStatus;
    }

    public function getProductTaxes()
    {
        $taxes = Tax::where('is_active', true)->get()->map(function ($tax) {
            return [
                'label' => $tax->name,
                'value' => $tax->rate,
            ];
        });

        // $productTaxes = [];
        // foreach($taxes as $key=>$tax)
        // {
        //     $productTaxes[$key]["label"] = $tax->name;
        //     $productTaxes[$key]["value"] = $tax->rate;
        // }

        return $taxes;
    }

    public function getWarrentyType()
    {
        $warrentyType = [
            ["label" => "Days", "value" => "days"],
            ["label" => "Months", "value" => "months"],
            ["label" => "Years", "value" => "years"]
        ];

        return $warrentyType;
    }

    public function getOrderDiscountType()
    {
        $orderDiscountType = [
            ["label" => "Flat", "value" => "Flat"],
            ["label" => "Percentage", "value" => "Percentage"]
        ];

        return $orderDiscountType;
    }

    public function DiscountTypes()
    {
        $discountTypes = [
            ["label" => "Percentage (%)", "value" => "percentage"],
            ["label" => "Flat", "value" => "flat"]
        ];

        return $discountTypes;
    }

    public function DiscountApplicable()
    {
        $discountApplicable = [
            ["label" => "All Products", "value" => "All"],
            ["label" => "Specific Products", "value" => "Specific"]
        ];

        return $discountApplicable;
    }

    public function weekDays()
    {
        $days = [
            ["label" => "Monday", "value" => "Mon"],
            ["label" => "Tuesday", "value" => "Tue"],
            ["label" => "Wednesday", "value" => "Wed"],
            ["label" => "Thursday", "value" => "Thu"],
            ["label" => "Friday", "value" => "Fri"],
            ["label" => "Saturday", "value" => "Sat"],
            ["label" => "Sunday", "value" => "Sun"],

        ];

        return $days;
    }

    public function accountTypes()
    {
        $accountType = [
            ["label" => "All", "value" => "0"],
            ["label" => "Debit", "value" => "1"],
            ["label" => "Credit", "value" => "2"],
        ];

        return $accountType;
    }
}
