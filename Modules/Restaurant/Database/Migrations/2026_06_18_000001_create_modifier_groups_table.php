<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModifierGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('selection_type', ['single', 'multiple'])->default('single')
                  ->comment('single = radio buttons, multiple = checkboxes');
            $table->tinyInteger('min_selection')->unsigned()->default(0)
                  ->comment('Minimum number of selections required');
            $table->tinyInteger('max_selection')->unsigned()->default(1)
                  ->comment('Maximum number of selections allowed');
            $table->tinyInteger('is_required')->default(0)
                  ->comment('1 = customer must select at least min_selection');
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modifier_groups');
    }
}
