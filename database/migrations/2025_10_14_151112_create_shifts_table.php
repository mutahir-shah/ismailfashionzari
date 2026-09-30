<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_in')->default(0)->comment('Grace period (minutes) before marking late');
            $table->integer('grace_out')->default(0)->comment('Grace period (minutes) before marking early leave');
            $table->decimal('total_hours', 5, 2)->nullable()->comment('Total working hours for the shift');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'shift_id')) {
                $table->dropConstrainedForeignId('shift_id');
            }
        });

        Schema::dropIfExists('shifts');
    }
};
