<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
        $table->string('type')->nullable()->after('employee_id');
    });
}

public function down()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->dropColumn(['employee_id', 'type']);
    });
}

};
