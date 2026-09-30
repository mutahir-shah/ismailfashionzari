<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('money_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('money_transfers', 'currency_id')) {
                $table->unsignedInteger('currency_id')->nullable()->after('amount');
            }

            if (!Schema::hasColumn('money_transfers', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency_id');
            }

            if (!Schema::hasColumn('money_transfers', 'note')) {
                $table->text('note')->nullable()->after('exchange_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('money_transfers', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('money_transfers', 'note')) {
                $columns[] = 'note';
            }

            if (Schema::hasColumn('money_transfers', 'exchange_rate')) {
                $columns[] = 'exchange_rate';
            }

            if (Schema::hasColumn('money_transfers', 'currency_id')) {
                $columns[] = 'currency_id';
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
