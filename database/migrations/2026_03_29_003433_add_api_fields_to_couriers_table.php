<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiFieldsToCouriersTable extends Migration
{
    public function up()
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
            $table->string('client_id')->nullable()->after('is_active');
            $table->string('client_secret')->nullable()->after('client_id');
            $table->string('username')->nullable()->after('client_secret');
            $table->string('password')->nullable()->after('username');
            $table->string('base_url')->nullable()->after('password');
        });
    }

    public function down()
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'client_id',
                'client_secret',
                'username',
                'password',
                'base_url',
            ]);
        });
    }
}
