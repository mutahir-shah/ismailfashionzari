<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = Schema::getColumnListing('products');

            $newColumns = [
                'slug'              => fn() => $table->string('slug')->nullable()->after('name'),
                'short_description' => fn() => $table->text('short_description')->nullable()->after('product_details'),
                'meta_title'        => fn() => $table->string('meta_title')->nullable()->after('short_description'),
                'meta_description'  => fn() => $table->text('meta_description')->nullable()->after('meta_title'),
                'related_products'  => fn() => $table->longText('related_products')->nullable()->after('variant_value'),
                'extras'            => fn() => $table->longText('extras')->nullable()->after('related_products'),
                'tags'              => fn() => $table->string('tags')->nullable()->after('extras'),
                'is_online'         => fn() => $table->tinyInteger('is_online')->nullable()->after('featured'),
                'in_stock'          => fn() => $table->tinyInteger('in_stock')->nullable()->after('is_online'),
                'is_addon'          => fn() => $table->tinyInteger('is_addon')->default(0)->after('in_stock'),
                'kitchen_id'        => fn() => $table->tinyInteger('kitchen_id')->nullable()->after('is_addon'),
                'menu_type'         => fn() => $table->text('menu_type')->nullable()->after('kitchen_id'),
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

    }
}
