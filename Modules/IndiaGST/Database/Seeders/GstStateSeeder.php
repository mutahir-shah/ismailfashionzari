<?php

namespace Modules\IndiaGST\Database\Seeders;

use Illuminate\Database\Seeder;

class GstStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['state_code' => '01', 'name' => 'Jammu and Kashmir', 'type' => 'UT', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '02', 'name' => 'Himachal Pradesh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '03', 'name' => 'Punjab', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '04', 'name' => 'Chandigarh', 'type' => 'UT', 'gst_jurisdiction_type' => 'utgst'],
            ['state_code' => '05', 'name' => 'Uttarakhand', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '06', 'name' => 'Haryana', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '07', 'name' => 'Delhi', 'type' => 'UT', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '08', 'name' => 'Rajasthan', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '09', 'name' => 'Uttar Pradesh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '10', 'name' => 'Bihar', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '11', 'name' => 'Sikkim', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '12', 'name' => 'Arunachal Pradesh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '13', 'name' => 'Nagaland', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '14', 'name' => 'Manipur', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '15', 'name' => 'Mizoram', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '16', 'name' => 'Tripura', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '17', 'name' => 'Meghalaya', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '18', 'name' => 'Assam', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '19', 'name' => 'West Bengal', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '20', 'name' => 'Jharkhand', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '21', 'name' => 'Odisha', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '22', 'name' => 'Chhattisgarh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '23', 'name' => 'Madhya Pradesh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '24', 'name' => 'Gujarat', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '26', 'name' => 'Dadra and Nagar Haveli and Daman and Diu', 'type' => 'UT', 'gst_jurisdiction_type' => 'utgst'],
            ['state_code' => '27', 'name' => 'Maharashtra', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '29', 'name' => 'Karnataka', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '30', 'name' => 'Goa', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '31', 'name' => 'Lakshadweep', 'type' => 'UT', 'gst_jurisdiction_type' => 'utgst'],
            ['state_code' => '32', 'name' => 'Kerala', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '33', 'name' => 'Tamil Nadu', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '34', 'name' => 'Puducherry', 'type' => 'UT', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '35', 'name' => 'Andaman and Nicobar Islands', 'type' => 'UT', 'gst_jurisdiction_type' => 'utgst'],
            ['state_code' => '36', 'name' => 'Telangana', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '37', 'name' => 'Andhra Pradesh', 'type' => 'STATE', 'gst_jurisdiction_type' => 'sgst'],
            ['state_code' => '38', 'name' => 'Ladakh', 'type' => 'UT', 'gst_jurisdiction_type' => 'utgst'],
            ['state_code' => '97', 'name' => 'Other Territory', 'type' => 'OTHER', 'gst_jurisdiction_type' => 'sgst'],
        ];

        foreach ($states as $state) {
            \Modules\IndiaGST\Entities\IndiaGstState::updateOrCreate(
                ['state_code' => $state['state_code']],
                [
                    'name' => $state['name'],
                    'type' => $state['type'],
                    'gst_jurisdiction_type' => $state['gst_jurisdiction_type']
                ]
            );
        }
    }
}
