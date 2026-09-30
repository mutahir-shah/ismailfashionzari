<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultipleColumnToCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('page_title')->nullable()->after('parent_id');
            $table->text('short_description')->nullable()->after('page_title');
            $table->string('slug')->nullable()->after('short_description');
            $table->string('icon')->nullable()->after('slug');
            $table->tinyInteger('featured')->default(1)->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['page_title',  'short_description', 'slug', 'icon', 'show_home', 'show_home_title', 'show_on_side_menu']);
        });
    }
}
