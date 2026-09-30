<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'project_category_list',
            'project_category_add',
            'project_category_edit',
            'project_category_delete'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'project_category_list',
            'project_category_add',
            'project_category_edit',
            'project_category_delete'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::where('name', $permission)->delete();
        }
    }
};
