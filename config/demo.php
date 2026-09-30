<?php

return [
    'enabled' => env('DEMO_SEEDER_ENABLED', true),

    // Data Volume Limits
    'product_count' => 1000,
    'customer_count' => 300,
    'supplier_count' => 80,
    'sales_months' => 12,
    'warehouse_count' => 7,

    // Image Configuration
    'use_placeholders' => true,
    'placeholder_url' => 'https://placehold.co/600x400/eeeeee/333333?text=',

    // Seed Flags
    'truncate_tables' => true, // Warning: This will delete existing data
];
