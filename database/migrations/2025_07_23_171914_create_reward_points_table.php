<?php

use App\Enums\RewardPointTypeEnum;
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
        Schema::create('reward_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->enum('reward_point_type', RewardPointTypeEnum::toArray())
                    ->default(RewardPointTypeEnum::AUTOMATIC->value);
            $table->decimal('points')->default(0);
            $table->decimal('deducted_points')->default(0);
            $table->string('note')->nullable();
            $table->datetime('expired_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_points');
    }
};
