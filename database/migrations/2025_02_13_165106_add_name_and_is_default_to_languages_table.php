<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->renameColumn('code', 'language');
            $table->string('name');
            $table->boolean('is_default')->default(false);
        });
    }

    public function down()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->renameColumn('language', 'code');
            $table->dropColumn(['name', 'is_default']);
        });
    }
};
