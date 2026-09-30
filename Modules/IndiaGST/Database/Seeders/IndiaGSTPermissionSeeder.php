<?php

namespace Modules\IndiaGST\Database\Seeders;

use Illuminate\Database\Seeder;

class IndiaGSTPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Phase 1A
            ['name' => 'india_gst.settings', 'guard_name' => 'web'],
            
            // Phase 1B
            ['name' => 'gst_tax_profiles.view', 'guard_name' => 'web'],
            ['name' => 'gst_tax_profiles.manage', 'guard_name' => 'web'],
            ['name' => 'gst_generic_tax_mappings.manage', 'guard_name' => 'web'],
            ['name' => 'gst_calculation.preview', 'guard_name' => 'web'],
            ['name' => 'gst_place_of_supply.override', 'guard_name' => 'web'],
            ['name' => 'gst_tax_profiles.override', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => $permission['guard_name']]
            );
        }
    }
}
