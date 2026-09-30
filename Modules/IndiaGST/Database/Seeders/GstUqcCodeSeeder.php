<?php

namespace Modules\IndiaGST\Database\Seeders;

use Illuminate\Database\Seeder;

class GstUqcCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common GST UQC codes
        $uqcCodes = [
            'BAG' => 'BAGS',
            'BAL' => 'BALE',
            'BDL' => 'BUNDLES',
            'BKL' => 'BUCKLES',
            'BOU' => 'BILLIONS OF UNITS',
            'BOX' => 'BOX',
            'BTL' => 'BOTTLES',
            'BUN' => 'BUNCHES',
            'CAN' => 'CANS',
            'CBM' => 'CUBIC METER',
            'CCM' => 'CUBIC CENTIMETER',
            'CMS' => 'CENTIMETER',
            'CTN' => 'CARTONS',
            'DOZ' => 'DOZEN',
            'DRM' => 'DRUM',
            'GGR' => 'GREAT GROSS',
            'GMS' => 'GRAMS',
            'GRS' => 'GROSS',
            'GYD' => 'GROSS YARDS',
            'KGS' => 'KILOGRAMS',
            'KLR' => 'KILOLITRE',
            'KME' => 'KILOMETRE',
            'MLT' => 'MILLILITRE',
            'MTR' => 'METERS',
            'NOS' => 'NUMBERS',
            'PAC' => 'PACKS',
            'PCS' => 'PIECES',
            'PRS' => 'PAIRS',
            'QTL' => 'QUINTAL',
            'ROL' => 'ROLLS',
            'SET' => 'SETS',
            'SQF' => 'SQUARE FEET',
            'SQM' => 'SQUARE METERS',
            'SQY' => 'SQUARE YARDS',
            'TBS' => 'TABLETS',
            'TGM' => 'TEN GROSS',
            'THD' => 'THOUSANDS',
            'TON' => 'TONNES',
            'TUB' => 'TUBES',
            'UGS' => 'US GALLONS',
            'UNT' => 'UNITS',
            'YDS' => 'YARDS',
            'OTH' => 'OTHERS',
        ];

        // This seeder won't auto-map to salepro_unit_id since that depends on the tenant's units
        // However, we can create a central table or keep it as an enum. Wait!
        // The migration `india_gst_uqc_mappings` links `salepro_unit_id` to `uqc_code`.
        // We only seed mappings if there are default units we want to map.
        // Let's attempt to map some standard SalePro units if they exist.
        $standardUnits = \App\Models\Unit::all();
        foreach ($standardUnits as $unit) {
            $code = $unit->unit_code;
            $uqc = null;
            if (in_array(strtoupper($code), ['PC', 'PCS', 'PIECE', 'PIECES'])) $uqc = 'PCS';
            elseif (in_array(strtoupper($code), ['KG', 'KGS', 'KILOGRAM', 'KILOGRAMS'])) $uqc = 'KGS';
            elseif (in_array(strtoupper($code), ['M', 'MTR', 'METER', 'METERS'])) $uqc = 'MTR';
            elseif (in_array(strtoupper($code), ['L', 'LTR', 'LITER', 'LITERS'])) $uqc = 'KLR'; // Note: typically KLR for Kilolitre, MLT for millilitre
            elseif (in_array(strtoupper($code), ['BOX', 'BOXES'])) $uqc = 'BOX';
            elseif (in_array(strtoupper($code), ['DOZ', 'DOZEN'])) $uqc = 'DOZ';
            elseif (in_array(strtoupper($code), ['NOS', 'NUMBER', 'NUMBERS'])) $uqc = 'NOS';
            
            if ($uqc) {
                \Modules\IndiaGST\Entities\IndiaGstUqcMapping::updateOrCreate(
                    ['salepro_unit_id' => $unit->id],
                    ['uqc_code' => $uqc]
                );
            }
        }
    }
}
