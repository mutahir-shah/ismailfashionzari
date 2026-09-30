<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Translation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('translations')) {
            return;
        }

        $translations = [
            ['key' => 'POS Type', 'value' => 'POS Type'],
            ['key' => 'Regular POS', 'value' => 'Regular POS'],
            ['key' => 'Restaurant POS', 'value' => 'Restaurant POS'],
            ['key' => 'Regular + Restaurant', 'value' => 'Regular + Restaurant'],
            ['key' => 'POS Workflow', 'value' => 'POS Workflow'],
            ['key' => 'pos_workflow', 'value' => 'POS Workflow'],
            ['key' => 'Choose POS Workflow', 'value' => 'Choose POS Workflow'],
            ['key' => 'choose_pos_workflow', 'value' => 'Choose POS Workflow'],
            ['key' => 'Retail', 'value' => 'Retail'],
            ['key' => 'retail', 'value' => 'Retail'],
            ['key' => 'Restaurant', 'value' => 'Restaurant'],
            ['key' => 'restaurant', 'value' => 'Restaurant'],
            ['key' => 'Retail POS', 'value' => 'Retail POS'],
            ['key' => 'Switch to Restaurant POS', 'value' => 'Switch to Restaurant POS'],
            ['key' => 'Switch to Retail POS', 'value' => 'Switch to Retail POS'],
            ['key' => 'Switch & Clear Cart', 'value' => 'Switch & Clear Cart'],
            ['key' => 'switch_and_clear_cart', 'value' => 'Switch & Clear Cart'],
            ['key' => 'supports_both_retail_and_restaurant', 'value' => 'supports both Retail and Restaurant sales.'],
            ['key' => 'pos_workflow_switch_confirm', 'value' => 'Your current cart and temporary POS data will be cleared because Retail and Restaurant orders use different workflows.'],
        ];

        foreach ($translations as $item) {
            DB::table('translations')->updateOrInsert(
                [
                    'locale' => 'en',
                    'group' => 'db',
                    'key' => $item['key'],
                ],
                [
                    'value' => $item['value'],
                    'updated_at' => now(),
                ]
            );
        }

        if (class_exists(Translation::class)) {
            Translation::forgetCachedTranslations();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('translations')) {
            return;
        }

        DB::table('translations')
            ->where('locale', 'en')
            ->where('group', 'db')
            ->whereIn('key', [
                'POS Type', 'Regular POS', 'Restaurant POS', 'Regular + Restaurant',
                'POS Workflow', 'pos_workflow', 'Choose POS Workflow', 'choose_pos_workflow',
                'Retail', 'retail', 'Restaurant', 'restaurant', 'Retail POS',
                'Switch to Restaurant POS', 'Switch to Retail POS',
                'Switch & Clear Cart', 'switch_and_clear_cart',
                'supports_both_retail_and_restaurant', 'pos_workflow_switch_confirm',
            ])
            ->delete();

        if (class_exists(Translation::class)) {
            Translation::forgetCachedTranslations();
        }
    }
};
