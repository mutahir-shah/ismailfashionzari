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
        $tableNames = config('permission.table_names', []);
        $columnNames = config('permission.column_names', []);

        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        if (! Schema::hasTable($modelHasPermissionsTable)) {
            Schema::create($modelHasPermissionsTable, function (Blueprint $table) use ($permissionsTable, $modelMorphKey) {
                $table->unsignedInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger($modelMorphKey);

                $table->index([$modelMorphKey, 'model_type'], 'mhp_model_index');
                $table->foreign('permission_id')
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');

                $table->primary(['permission_id', $modelMorphKey, 'model_type'], 'mhp_permission_model_primary');
            });
        }

        if (! Schema::hasTable($modelHasRolesTable)) {
            Schema::create($modelHasRolesTable, function (Blueprint $table) use ($rolesTable, $modelMorphKey) {
                $table->unsignedInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger($modelMorphKey);

                $table->index([$modelMorphKey, 'model_type'], 'mhr_model_index');
                $table->foreign('role_id')
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');

                $table->primary(['role_id', $modelMorphKey, 'model_type'], 'mhr_role_model_primary');
            });
        }

        app('cache')->forget('spatie.permission.cache');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names', []);

        Schema::dropIfExists($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::dropIfExists($tableNames['model_has_permissions'] ?? 'model_has_permissions');

        app('cache')->forget('spatie.permission.cache');
    }
};
