<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use App\Product;
use App\SellingPriceGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Cache;

class LabelsController extends Controller
{
    /**
     * Display labels
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $barcode_settings = Barcode::select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))->get();
        $default = $barcode_settings->where('is_default', 1)->first();
        $barcode_settings = $barcode_settings->pluck('name', 'id');

        return view('backend.labels.show', compact('barcode_settings'));
    }

    public function printLabel(Request $request)
    {
        try {
            $products        = $request->get('products');
            $print           = $request->get('print');
            $barcode_setting = $request->get('barcode_setting');

            $barcode_details = Barcode::findOrFail($barcode_setting);

            // Load business name
            $general_setting = Cache::remember('general_setting', 60 * 60 * 24 * 365, function () {
                return DB::table('general_settings')->latest()->first();
            });

            $business_name = $general_setting->company_name;

            $all_labels = [];
            $total_qty  = 0;

            foreach ($products as $value) {

                $details = [
                    'product_name'        => $value['product_name'],
                    'product_actual_name' => $value['product_name'],
                    'product_price'       => $value['product_price'] ?? $value['default_price'],
                    'product_promo_price' => $value['product_promo_price'],
                    'currency'            => $value['currency'],
                    'currency_position'   => $value['currency_position'],
                    'product_id'          => $value['product_id'],
                    'brand_name'          => $value['brand_name'],
                    'product_type'        => 'standard',
                    'sub_sku'             => $value['sub_sku'],
                    'barcode_type'        => 'C128',
                    'unit'                => 1,
                ];

                for ($i = 0; $i < $value['quantity']; $i++) {
                    $all_labels[] = $details;
                    $total_qty++;
                }
            }

            // Dumbbell Barcode
            if ($barcode_details->layout_type === 'dumbbell') {

                $barcode_details->paper_width  = $barcode_details->width;
                $barcode_details->paper_height = $barcode_details->height;

                return view('backend.labels.print_label_dumbbell', [
                    'labels'          => $all_labels,
                    'print'           => $print,
                    'business_name'   => $business_name,
                    'barcode_details' => $barcode_details,
                ]);
            }
            /*
            |--------------------------------------------------------------------------
            | CONTINUOUS ROLL PRINTING
            |--------------------------------------------------------------------------
            */
            if ($barcode_details->layout_type === 'continuous') {

                $barcode_details->paper_width  = $barcode_details->width;
                $barcode_details->paper_height = $barcode_details->height;

                return view('backend.labels.print_label_continuous')
                    ->with([
                        'print'           => $print,
                        'labels'          => $all_labels,
                        'business_name'   => $business_name,
                        'barcode_details' => $barcode_details,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | SHEET PRINTING
            |--------------------------------------------------------------------------
            */

            $stickers_per_sheet = $barcode_details->stickers_in_one_sheet;
            $product_details_page_wise = [];
            $page = 0;

            foreach ($all_labels as $index => $label) {

                if ($index % $stickers_per_sheet == 0) {
                    $product_details_page_wise[$page] = [];
                }

                $product_details_page_wise[$page][] = $label;

                if (($index + 1) % $stickers_per_sheet == 0) {
                    $page++;
                }
            }

            $html = '';

            foreach ($product_details_page_wise as $page_products) {

                $html .= view('backend.labels.print_label')
                    ->with([
                        'print'           => $print,
                        'page_products'   => $page_products,
                        'business_name'   => $business_name,
                        'barcode_details' => $barcode_details,
                        'margin_top'      => $barcode_details->top_margin,
                        'margin_left'     => $barcode_details->left_margin,
                        'paper_width'     => $barcode_details->paper_width,
                        'paper_height'    => $barcode_details->paper_height,
                    ])->render();
            }

            return response($html);
        } catch (\Exception $e) {

            \Log::emergency(
                'File:' . $e->getFile() .
                    ' Line:' . $e->getLine() .
                    ' Message:' . $e->getMessage()
            );

            return response(__('lang_v1.barcode_label_error'), 500);
        }
    }
}
