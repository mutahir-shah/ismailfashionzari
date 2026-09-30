<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = Schema::getColumnListing('sales');

            $newColumns = [
                'billing_name'     => fn() => $table->string('billing_name')->nullable()->after('paid_amount'),
                'billing_phone'    => fn() => $table->string('billing_phone')->nullable()->after('billing_name'),
                'billing_email'    => fn() => $table->string('billing_email')->nullable()->after('billing_phone'),
                'billing_address'  => fn() => $table->string('billing_address')->nullable()->after('billing_email'),
                'billing_city'     => fn() => $table->string('billing_city')->nullable()->after('billing_address'),
                'billing_state'    => fn() => $table->string('billing_state')->nullable()->after('billing_city'),
                'billing_country'  => fn() => $table->string('billing_country')->nullable()->after('billing_state'),
                'billing_zip'      => fn() => $table->string('billing_zip')->nullable()->after('billing_country'),

                'shipping_name'    => fn() => $table->string('shipping_name')->nullable()->after('billing_zip'),
                'shipping_phone'   => fn() => $table->string('shipping_phone')->nullable()->after('shipping_name'),
                'shipping_email'   => fn() => $table->string('shipping_email')->nullable()->after('shipping_phone'),
                'shipping_address' => fn() => $table->string('shipping_address')->nullable()->after('shipping_email'),
                'shipping_city'    => fn() => $table->string('shipping_city')->nullable()->after('shipping_address'),
                'shipping_state'   => fn() => $table->string('shipping_state')->nullable()->after('shipping_city'),
                'shipping_country' => fn() => $table->string('shipping_country')->nullable()->after('shipping_state'),
                'shipping_zip'     => fn() => $table->string('shipping_zip')->nullable()->after('shipping_country'),

                'service_id'       => fn() => $table->string('service_id')->nullable()->after('shipping_zip'),
                'waiter_id'        => fn() => $table->string('waiter_id')->nullable()->after('service_id'),
                'payment_mode'     => fn() => $table->string('payment_mode')->nullable()->after('waiter_id'),
            ];

            foreach ($newColumns as $name => $callback) {
                if (!in_array($name, $columns)) {
                    $callback();
                }
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {

        });
    }
}
