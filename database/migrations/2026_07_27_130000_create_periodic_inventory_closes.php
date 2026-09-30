<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodic_inventory_closes', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('posting_date');
            $table->string('scope')->default('company');
            $table->string('active_key')->nullable()->unique();
            $table->string('status')->default('posted');
            $table->decimal('book_inventory', 15, 4);
            $table->decimal('operational_inventory', 15, 4);
            $table->decimal('adjustment', 15, 4);
            $table->json('calculation_basis')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('reversal_journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->index(['period_start', 'period_end', 'scope'], 'inventory_close_period_scope');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('reversal_journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });

        if (Schema::hasTable('accounting_accounts') && DB::table('accounting_accounts')->exists()) {
            $accountId = DB::table('accounting_accounts')->where('code', '5000')->value('id');
            if (!$accountId) {
                $accountId = DB::table('accounting_accounts')->insertGetId([
                    'code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'cogs',
                    'is_control_account' => true, 'is_system' => true, 'is_active' => true,
                    'is_cash_account' => false, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('account_mappings')->updateOrInsert(
                ['mapped_type' => 'cost_of_goods_sold', 'mapped_id' => 0],
                ['accounting_account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (Schema::hasTable('translations')) {
            DB::table('translations')->updateOrInsert(
                ['locale' => 'en', 'group' => 'db', 'key' => 'inventory_close_title'],
                ['value' => 'Periodic Inventory Close', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('periodic_inventory_closes');
    }
};
