<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingEngineTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'cogs', 'expense']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('accounting_accounts')->onDelete('cascade');
            $table->index('account_type');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->date('entry_date');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Idempotency and polymorphic query index
            $table->unique(['source_type', 'source_id', 'event_type'], 'journal_entries_source_unique');
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('accounting_account_id');
            $table->decimal('debit', 15, 4)->default(0);
            $table->decimal('credit', 15, 4)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            $table->foreign('accounting_account_id')->references('id')->on('accounting_accounts')->onDelete('restrict');
        });

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounting_account_id');
            $table->string('mapped_type');
            $table->unsignedBigInteger('mapped_id');
            $table->timestamps();

            $table->foreign('accounting_account_id')->references('id')->on('accounting_accounts')->onDelete('cascade');
            $table->unique(['mapped_type', 'mapped_id'], 'account_mappings_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_accounts');
        Schema::dropIfExists('accounting_periods');
    }
}
