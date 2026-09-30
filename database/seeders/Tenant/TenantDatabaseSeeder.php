<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Traits\TenantInfo;

use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Services\AccountingModeService;
use App\Services\PaymentAccountService;

class TenantDatabaseSeeder extends Seeder
{
    use TenantInfo;
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public static $tenantData = [];

    public function run()
    {
        if (!DB::table('barcodes')->count()) {
            DB::table('barcodes')->insert([
                [
                    'name' => '20 Labels per Sheet',
                    'description' => 'Sheet Size: 8.5" x 11", Label Size: 4" x 1", Labels per sheet: 20',
                    'width' => 4.0000,
                    'height' => 1.0000,
                    'paper_width' => 8.5000,
                    'paper_height' => 11.0000,
                    'top_margin' => 0.5000,
                    'left_margin' => 0.1250,
                    'row_distance' => 0.0000,
                    'col_distance' => 0.1875,
                    'stickers_in_one_row' => 2,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'sheet',
                    'stickers_in_one_sheet' => 20,
                    'is_custom' => null
                ],
                [
                    'name' => '30 Labels per sheet',
                    'description' => 'Sheet Size: 8.5" x 11", Label Size: 2.625" x 1", Labels per sheet: 30',
                    'width' => 2.6250,
                    'height' => 1.0000,
                    'paper_width' => 8.5000,
                    'paper_height' => 11.0000,
                    'top_margin' => 0.5000,
                    'left_margin' => 0.1880,
                    'row_distance' => 0.0000,
                    'col_distance' => 0.1250,
                    'stickers_in_one_row' => 3,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'sheet',
                    'stickers_in_one_sheet' => 30,
                    'is_custom' => null
                ],
                [
                    'name' => '32 Labels per sheet',
                    'description' => 'Sheet Size: 8.5" x 11", Label Size: 2" x 1.25", Labels per sheet: 32',
                    'width' => 2.0000,
                    'height' => 1.2500,
                    'paper_width' => 8.5000,
                    'paper_height' => 11.0000,
                    'top_margin' => 0.5000,
                    'left_margin' => 0.2500,
                    'row_distance' => 0.0000,
                    'col_distance' => 0.0000,
                    'stickers_in_one_row' => 4,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'sheet',
                    'stickers_in_one_sheet' => 32,
                    'is_custom' => null
                ],
                [
                    'name' => '40 Labels per sheet',
                    'description' => 'Sheet Size: 8.5" x 11", Label Size: 2" x 1", Labels per sheet: 40',
                    'width' => 2.0000,
                    'height' => 1.0000,
                    'paper_width' => 8.5000,
                    'paper_height' => 11.0000,
                    'top_margin' => 0.5000,
                    'left_margin' => 0.2500,
                    'row_distance' => 0.0000,
                    'col_distance' => 0.0000,
                    'stickers_in_one_row' => 4,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'sheet',
                    'stickers_in_one_sheet' => 40,
                    'is_custom' => null
                ],
                [
                    'name' => '50 Labels per Sheet',
                    'description' => 'Sheet Size: 8.5" x 11", Label Size: 1.5" x 1", Labels per sheet: 50',
                    'width' => 1.5000,
                    'height' => 1.0000,
                    'paper_width' => 8.5000,
                    'paper_height' => 11.0000,
                    'top_margin' => 0.5000,
                    'left_margin' => 0.5000,
                    'row_distance' => 0.0000,
                    'col_distance' => 0.0000,
                    'stickers_in_one_row' => 5,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'sheet',
                    'stickers_in_one_sheet' => 50,
                    'is_custom' => null
                ],
                [
                    'name' => 'Continuous Rolls - 31.75mm x 25.4mm',
                    'description' => 'Label Size: 31.75mm x 25.4mm, Gap: 3.18mm',
                    'width' => 1.2500,
                    'height' => 1.0000,
                    'paper_width' => 1.2500,
                    'paper_height' => 0.0000,
                    'top_margin' => 0.1250,
                    'left_margin' => 0.0000,
                    'row_distance' => 0.1250,
                    'col_distance' => 0.0000,
                    'stickers_in_one_row' => 1,
                    'is_default' => 0,
                    'is_continuous' => 1,
                    'layout_type' => 'continuous',
                    'stickers_in_one_sheet' => null,
                    'is_custom' => null
                ],
                [
                    'name' => 'Dumbbell Label',
                    'description' => '50mm × 25mm Dumbbell Label',
                    'width' => 1.9685,
                    'height' => 0.9843,
                    'paper_width' => 1.9685,
                    'paper_height' => 0,
                    'top_margin' => 0,
                    'left_margin' => 0,
                    'row_distance' => 0.1181,
                    'col_distance' => 0,
                    'stickers_in_one_row' => 1,
                    'is_default' => 0,
                    'is_continuous' => 0,
                    'layout_type' => 'dumbbell',
                    'stickers_in_one_sheet' => null,
                    'is_custom' => null,
                ],
            ]);
        }

        //External Services Seeder started
        $newAddons = ['ecommerce']; // Future add-ons

        // Fetch all rows
        $gateways = DB::table('external_services')->where('type', 'payment')->get();

        foreach ($gateways as $gateway) {
            // Decode existing module_status, or initialize as empty
            $moduleStatus = json_decode($gateway->module_status, true) ?? [];

            // Ensure all add-ons are present in module_status with a default of false
            foreach ($newAddons as $addon) {
                if (!isset($moduleStatus[$addon])) {
                    $moduleStatus[$addon] = false;
                }
            }

            // Update the row
            DB::table('external_services')
                ->where('id', $gateway->id)
                ->update(['module_status' => json_encode($moduleStatus)]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'PayPal') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'PayPal',
                'type' => 'payment',
                'details' => 'Client ID,Client Secret;abcd1234,wxyz5678', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Stripe') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Stripe',
                'type' => 'payment',
                'details' => 'Public Key,Private Key;efgh1234,stuv5678', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Razorpay') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Razorpay',
                'type' => 'payment',
                'details' => 'Key,Secret;rzp_test_Y4MCcpHfZNU6rR,3Hr7SDqaZ0G5waN0jsLgsiLx', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Paystack') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Paystack',
                'type' => 'payment',
                'details' => 'public_Key,Secret_Key;pk_test_e8d220b7463d64569f0053e78534f38e6b10cf4a,sk_test_6d62cb976e1e0ab43f1e48b2934b0dfc7f32a1fe', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Mollie') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Mollie',
                'type' => 'payment',
                'details' => 'api_key;test_dHar4XY7LxsDOtmnkVtjNVWXLSlXsM', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Xendit') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Xendit',
                'type' => 'payment',
                'details' => 'secret_key,callback_token;xnd_development_aKJVKYbc4lHkEjcCLzWLrBsKs6jF6nbM6WaCMfnJerP3JW57CLis553XNRdDU,YPZxND92Mt8tdXntTYIEkRX802onZ5OcdKBUzycebuqYvN4n', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'bkash') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'bkash',
                'type' => 'payment',
                'details' => 'Mode,app_key,app_secret,username,password;sandbox,0vWQuCRGiUX7EPVjQDr0EUAYtc,jcUNPBgbcqEDedNKdvE4G1cAK7D3hCjmJccNPZZBq96QIxxwAMEx,01770618567,D7DaC<*E*eG', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'url') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'url',
                'type' => 'url',
                'details' => preg_replace('/^https?:\/\//', '', rtrim(url('/'), '/')), // Dummy values; users will update
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'sslcommerz') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'sslcommerz',
                'type' => 'payment',
                'details' => 'appkey,appsecret;12341234,asdfa23423', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Mpesa') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Mpesa',
                'type' => 'payment',
                'details' => 'consumer_Key,consumer_Secret;fhfgkj,dtrddhd', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Pesapal') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Pesapal',
                'type' => 'payment',
                'details' => 'Mode,Consumer Key,Consumer Secret;sandbox,qkio1BGGYAXTu2JOfm7XSXNruoZsrqEW,osGQ364R49cXKeOYSpaOnT++rHs=', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Moneipoint') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Moneipoint',
                'type' => 'payment',
                'details' => 'Mode,client_id,client_secret,terminal_serial;sandbox,api-client-3956952-7e1279e2-95d2-45e1-825a-3a28e0a35168,ZtH02Q%jQ$Imcf%W^B%q,C42P008D01909830', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'MtnMoMo') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'MtnMoMo',
                'type' => 'payment',
                'details' => 'Mode,subscription_key,api_user_id,api_key,country_code;sandbox,your_subscription_key,your_api_user_id,your_api_key,256', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'PayHere') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'PayHere',
                'type' => 'payment',
                'details' => 'Mode,merchant_id,merchant_secret;sandbox,your_merchant_id,your_merchant_secret', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add a new gateway if needed
        $newGateway = DB::table('external_services')
            ->where('name', 'Moyasar') // Replace with the actual name
            ->first();

        if (!$newGateway) {
            DB::table('external_services')->insert([
                'name' => 'Moyasar',
                'type' => 'payment',
                'details' => 'Mode,Publishable Key,Secret Key,Webhook Secret;test,pk_test_your_publishable_key,sk_test_your_secret_key,your_webhook_secret_token', // Dummy values; users will update
                'module_status' => json_encode(['ecommerce' => true, 'pos' => true]),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        //External Services Seeder ended

        if (DB::table('languages')->count() > 0) {

            $lang = DB::table('languages')->orderBy('id')->get(); // Fetch all languages ordered by ID

            $first = $lang->first(); // Get the first record from the collection
            $default = $lang->where('is_default', 1)->first(); // Check if there's already a default language

            if (!$default && $first) {
                // Set all is_default = 0
                DB::table('languages')->update(['is_default' => 0]);

                // Set is_default = 1 for the first row
                DB::table('languages')->where('id', $first->id)->update(['is_default' => 1]);
            }
        } else {

            DB::table('languages')->insert(array(
                0 =>
                    array(
                        'id' => 1,
                        'language' => 'en',
                        'created_at' => '2025-02-15 19:31:18',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'English',
                        'is_default' => 1,
                    ),
                1 =>
                    array(
                        'id' => 2,
                        'language' => 'bn',
                        'created_at' => '2025-02-15 19:31:36',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Bangla',
                        'is_default' => 0,
                    ),
                2 =>
                    array(
                        'id' => 3,
                        'language' => 'ar',
                        'created_at' => '2025-02-16 11:54:58',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Arabic',
                        'is_default' => 0,
                    ),
                3 =>
                    array(
                        'id' => 4,
                        'language' => 'al',
                        'created_at' => '2025-02-20 19:07:34',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Albania',
                        'is_default' => 0,
                    ),
                4 =>
                    array(
                        'id' => 5,
                        'language' => 'az',
                        'created_at' => '2025-02-23 10:43:59',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Azerbaijan',
                        'is_default' => 0,
                    ),
                5 =>
                    array(
                        'id' => 6,
                        'language' => 'bg',
                        'created_at' => '2025-02-23 10:52:01',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Bulgaria',
                        'is_default' => 0,
                    ),
                6 =>
                    array(
                        'id' => 7,
                        'language' => 'de',
                        'created_at' => '2025-02-23 11:04:53',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Germany',
                        'is_default' => 0,
                    ),
                7 =>
                    array(
                        'id' => 8,
                        'language' => 'es',
                        'created_at' => '2025-02-23 11:10:30',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Spanish',
                        'is_default' => 0,
                    ),
                8 =>
                    array(
                        'id' => 9,
                        'language' => 'fr',
                        'created_at' => '2025-02-23 14:12:28',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'French',
                        'is_default' => 0,
                    ),
                9 =>
                    array(
                        'id' => 10,
                        'language' => 'id',
                        'created_at' => '2025-02-23 14:13:28',
                        'updated_at' => '2025-02-23 14:24:00',
                        'name' => 'Indonesian',
                        'is_default' => 0,
                    ),
                10 =>
                    array(
                        'id' => 11,
                        'language' => 'tr',
                        'created_at' => '2025-03-20 12:55:53',
                        'updated_at' => '2025-03-20 12:55:53',
                        'name' => 'Turkish',
                        'is_default' => 0,
                    ),
                11 =>
                    array(
                        'id' => 12,
                        'language' => 'vi',
                        'created_at' => '2025-03-24 16:49:54',
                        'updated_at' => '2025-03-24 16:49:54',
                        'name' => 'Vietnamese',
                        'is_default' => 0,
                    ),
                12 =>
                    array(
                        'id' => 13,
                        'language' => 'pt',
                        'created_at' => '2025-03-24 16:49:54',
                        'updated_at' => '2025-03-24 16:49:54',
                        'name' => 'Portuguese',
                        'is_default' => 0,
                    ),
                13 =>
                    array(
                        'id' => 14,
                        'language' => 'ms',
                        'created_at' => '2025-03-24 16:49:54',
                        'updated_at' => '2025-03-24 16:49:54',
                        'name' => 'Malay',
                        'is_default' => 0,
                    ),
                14 =>
                    array(
                        'id' => 15,
                        'language' => 'sr',
                        'created_at' => '2025-03-24 16:49:54',
                        'updated_at' => '2025-03-24 16:49:54',
                        'name' => 'Serbian',
                        'is_default' => 0,
                    ),
            ));
        }

        //Translations Table Seeder Started
        // Step 1: Load existing translations from the database
        $existing = DB::table('translations')
            ->select('locale', 'group', 'key')
            ->get();

        $existingMap = [];

        foreach ($existing as $item) {
            $group = $item->group ?? 'db';
            $existingMap[$item->locale . '|' . $group . '|' . $item->key] = true;
        }

        // Step 2: Load all locale files
        $directory = database_path('seeders/Tenant/translations');
        $files = glob($directory . '/*.php');

        $insertData = [];

        foreach ($files as $file) {
            $data = include $file;
            $locale = basename($file, '.php');

            foreach ($data as $row) {
                $lookupKey = $locale . '|db|' . $row['key'];

                if (!isset($existingMap[$lookupKey])) {
                    $insertData[] = [
                        'locale' => $locale,
                        'group' => 'db',
                        'key' => $row['key'],
                        'value' => $row['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $existingMap[$lookupKey] = true;
                }
            }
        }

        // Step 3: Insert new records in chunks
        if (!empty($insertData)) {
            $chunks = collect($insertData)->chunk(1000);
            foreach ($chunks as $chunk) {
                DB::table('translations')->insert($chunk->toArray());
            }
        }

        if (class_exists(\App\Models\Translation::class)) {
            \App\Models\Translation::forgetCachedTranslations();
        }
        //Translations Table Seeder Ended

        if (!config('database.connections.saleprosaas_landlord')) {
            if (!DB::table('theme_settings')->count()) {
                $defaults = [
                    [
                        'name' => 'Indigo',
                        'theme_color' => '#6366F1', // 500 shade only
                        'font_family' => 'Jost',
                        'icon_pack' => 'solar',
                        'item_size' => 16,
                        'input_design' => 'outlined',
                        'button_style' => 'gradient',
                        'button_colors' => json_encode(['#6366F1', '#8B5CF6']),
                        'border_radius' => 'rounded-lg',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                    [
                        'name' => 'Green',
                        'theme_color' => '#10B981', // 500 shade only
                        'font_family' => 'Poppins',
                        'icon_pack' => 'fontawesome',
                        'item_size' => 16,
                        'input_design' => 'filled',
                        'button_style' => 'filled',
                        'button_colors' => null,
                        'border_radius' => 'rounded-none',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                    [
                        'name' => 'Blue',
                        'theme_color' => '#3B82F6', // 500 shade only
                        'font_family' => 'Roboto',
                        'icon_pack' => 'material',
                        'item_size' => 16,
                        'input_design' => 'outlined',
                        'button_style' => 'outlined',
                        'button_colors' => null,
                        'border_radius' => 'rounded',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                    [
                        'name' => 'Violet',
                        'theme_color' => '#8b5cf6', // 500 shade only
                        'font_family' => 'Roboto',
                        'icon_pack' => 'cupertino',
                        'item_size' => 16,
                        'input_design' => 'outlined',
                        'button_style' => 'gradient',
                        'button_colors' => json_encode(['#a78bfa', '#7c3aed']),
                        'border_radius' => 'rounded-lg',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                    [
                        'name' => 'Red',
                        'theme_color' => '#F43F5E', // 500 shade only
                        'font_family' => 'Nunito',
                        'icon_pack' => 'heroicons',
                        'item_size' => 16,
                        'input_design' => 'filled',
                        'button_style' => 'filled',
                        'button_colors' => null,
                        'border_radius' => 'rounded-full',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                    [
                        'name' => 'Orange',
                        'theme_color' => '#F97316', // 500 shade only
                        'font_family' => 'Raleway',
                        'icon_pack' => 'bootstrap',
                        'item_size' => 16,
                        'input_design' => 'filled',
                        'button_style' => 'outlined',
                        'button_colors' => null,
                        'border_radius' => 'rounded-full',
                        'sidebar_style' => 'normal',
                        'sidebar_corner' => 'rounded',
                        'auth_background_type' => 'themed',
                        'is_active' => true,
                        'active_for' => json_encode(['app']),
                        'is_deleted' => false,
                    ],
                ];
                DB::table('theme_settings')->insert($defaults);
            }
        } else {
            if (!DB::table('theme_settings')->count()) {
                $theme_settings = [];
                tenancy()->central(function () use (&$theme_settings) {
                    $theme_settings = DB::table('theme_settings')
                        ->get()
                        ->map(fn($item) => collect($item)->except('id')->toArray())
                        ->toArray();
                });
                DB::table('theme_settings')->insert($theme_settings);
            }
        }

        if (!DB::table('invoice_settings')->count()) {
            $baseData = [
                'prefix' => !empty(self::$tenantData) ? mb_substr(str_replace(' ', '', self::$tenantData['site_title']), 0, 10) : 'salepro',
                'number_of_digit' => 4,
                'numbering_type' => 'datewise',
                'start_number' => 1000,
                'header_text' => !empty(self::$tenantData) ? self::$tenantData['site_title'] : 'SalePro',
                'footer_text' => 'Thank you for shopping with us',
                'footer_title' => 'Thank you for shopping with us',
                'size' => 'a4',
                'logo_height' => 200,
                'logo_width' => 200,
                'primary_color' => '#ff0000',
                'text_color' => '#000000',
                'invoice_date_format' => 'd.m.y h:i A',
                'is_default' => 0,
                'status' => 0,
                'show_column' => json_encode([
                    "is_default" => 0,
                    "status" => 0,
                    "show_barcode" => 1,
                    "show_qr_code" => 1,
                    "show_customer_details" => 1,
                    "show_shipping_details" => 1,
                    "show_payment_info" => 1,
                    "show_discount" => 1,
                    "show_tax_info" => 1,
                    "show_description" => 1,
                    "show_in_words" => 1,
                    "active_primary_color" => 0,
                    "active_text_color" => 0,
                    "show_warehouse_info" => 1,
                    "show_bill_to_info" => 1,
                    "show_footer_text" => 1,
                    "show_biller_info" => 1,
                    "show_payment_note" => 1,
                    "show_paid_info" => 1,
                    "show_ref_number" => 1,
                    "show_customer_name" => 1,
                    "active_date_format" => 0,
                    "active_generat_settings" => 0,
                    "active_logo_height_width" => 0,
                    "hide_total_due" => 0,
                ]),
            ];

            $templates = [
                'A4 Size Normal Invoice',
                '58mm Thermal Invoice',
                '80mm Thermal Invoice',
            ];

            $size = [
                'a4',
                '58mm',
                '80mm',
            ];

            $value = [
                1,
                0,
                0,
            ];

            foreach ($templates as $key => $name) {
                DB::table('invoice_settings')->insert(array_merge($baseData, [
                    'template_name' => $name,
                    'size' => $size[$key],
                    'is_default' => $value[$key]
                ]));
            }
        }

        if (!DB::table('general_settings')->count()) {
            DB::table('general_settings')->insert([
                [
                    'id' => 1,
                    'site_title' => !empty(self::$tenantData) ? self::$tenantData['site_title'] : 'SalePro',
                    'site_logo' => !empty(self::$tenantData) ? self::$tenantData['site_logo'] : '20250123024505.png',
                    'is_rtl' => 0,
                    'currency' => '1',
                    'package_id' => !empty(self::$tenantData) ? self::$tenantData['package_id'] : 0,
                    'subscription_type' => !empty(self::$tenantData) ? self::$tenantData['subscription_type'] : 'monthly',
                    'staff_access' => 'own',
                    'without_stock' => 'no',
                    'date_format' => 'd/m/Y',
                    'developed_by' => !empty(self::$tenantData) ? self::$tenantData['developed_by'] : 'Lioncoders',
                    'invoice_format' => 'standard',
                    'decimal' => 2,
                    'state' => 1,
                    'theme' => 'default.css',
                    'modules' => !empty(self::$tenantData) ? self::$tenantData['modules'] : '',
                    'currency_position' => 'prefix',
                    'expiry_date' => !empty(self::$tenantData) ? self::$tenantData['expiry_date'] : '1970-01-01',
                    'expiry_type' => 'days',
                    'expiry_value' => '0',
                    'is_zatca' => NULL,
                    'company_name' => NULL,
                    'vat_registration_number' => NULL,
                    'is_packing_slip' => 0,
                ]
            ]);
        }

        if (!DB::table('users')->count()) {
            DB::table('users')->insert([
                [
                    'id' => 1,
                    'name' => !empty(self::$tenantData) ? self::$tenantData['name'] : 'admin',
                    'email' => !empty(self::$tenantData) ? self::$tenantData['email'] : 'admin@gmail.com',
                    'password' => !empty(self::$tenantData) ? self::$tenantData['password'] : '$2y$10$DWAHTfjcvwCpOCXaJg11MOhsqns03uvlwiSUOQwkHL2YYrtrXPcL6',
                    'remember_token' => '6mN44MyRiQZfCi0QvFFIYAU9LXIUz9CdNIlrRS5Lg8wBoJmxVu8auzTP42ZW',
                    'phone' => !empty(self::$tenantData) ? self::$tenantData['phone'] : '12112',
                    'company_name' => !empty(self::$tenantData) ? self::$tenantData['company_name'] : 'lioncoders',
                    'role_id' => 1,
                    'biller_id' => NULL,
                    'warehouse_id' => NULL,
                    'is_active' => 1,
                    'is_deleted' => 0,
                ]
            ]);
        }

        if (!DB::table('roles')->count()) {
            DB::table('roles')->insert([
                [
                    'id' => 1,
                    'name' => 'Admin',
                    'description' => 'admin can access all data...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 2,
                    'name' => 'Owner',
                    'description' => 'Staff of shop',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 4,
                    'name' => 'staff',
                    'description' => 'staff has specific acess...',
                    'is_active' => 1,
                    'guard_name' => 'web',
                ],
                [
                    'id' => 5,
                    'name' => 'Customer',
                    'description' => NULL,
                    'is_active' => 1,
                    'guard_name' => 'web',
                ]
            ]);
        }

        ///permissions table data insert start///
        $existing_permissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $existingMap = array_flip($existing_permissions);

        $permission_data = [
            [
                'name' => 'products-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'stock-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'due-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tax-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'profit-loss',
                'guard_name' => 'web',
            ],
            [
                'name' => 'best-seller',
                'guard_name' => 'web',
            ],
            [
                'name' => 'daily-sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly-sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'daily-purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly-purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payment-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-stock-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-qty-alert',
                'guard_name' => 'web',
            ],
            [
                'name' => 'supplier-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'general_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'mail_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'pos_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'hrm_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'chart-of-accounts-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'semantic-account-mappings-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'balance-sheet',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-statement',
                'guard_name' => 'web',
            ],
            [
                'name' => 'department',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attendance',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payroll',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'user-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'stock_count',
                'guard_name' => 'web',
            ],
            [
                'name' => 'adjustment',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sms_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'create_sms',
                'guard_name' => 'web',
            ],
            [
                'name' => 'print_barcode',
                'guard_name' => 'web',
            ],
            [
                'name' => 'empty_database',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer_group',
                'guard_name' => 'web',
            ],
            [
                'name' => 'unit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tax',
                'guard_name' => 'web',
            ],
            [
                'name' => 'gift_card',
                'guard_name' => 'web',
            ],
            [
                'name' => 'coupon',
                'guard_name' => 'web',
            ],
            [
                'name' => 'holiday',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'warehouse',
                'guard_name' => 'web',
            ],
            [
                'name' => 'brand',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'money-transfer',
                'guard_name' => 'web',
            ],
            [
                'name' => 'category',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delivery',
                'guard_name' => 'web',
            ],
            [
                'name' => 'send_notification',
                'guard_name' => 'web',
            ],
            [
                'name' => 'today_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'today_profit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'currency',
                'guard_name' => 'web',
            ],
            [
                'name' => 'backup_database',
                'guard_name' => 'web',
            ],
            [
                'name' => 'reward_point_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'revenue_profit_summary',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cash_flow',
                'guard_name' => 'web',
            ],
            [
                'name' => 'monthly_summary',
                'guard_name' => 'web',
            ],
            [
                'name' => 'yearly_report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'discount_plan',
                'guard_name' => 'web',
            ],
            [
                'name' => 'discount',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product-expiry-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'all_notification',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-report-chart',
                'guard_name' => 'web',
            ],
            [
                'name' => 'dso-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product_history',
                'guard_name' => 'web',
            ],
            [
                'name' => 'supplier-due-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'custom_field',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'packing_slip_challan',
                'guard_name' => 'web',
            ],
            [
                'name' => 'biller-report',
                'guard_name' => 'web',
            ],
            [
                'name' => 'payment_gateway_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'barcode_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'language_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'addons',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-selection',
                'guard_name' => 'web',
            ],
            [
                'name' => 'invoice_setting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'invoice_create_edit_delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'handle_discount',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'role_permission',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cart-product-update',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-import',
                'guard_name' => 'web',
            ],
            [
                'name' => 'change_sale_date',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_product',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_purchase',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_quotation',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_transfer',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_expense',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_income',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_accounting',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_hrm',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_people',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_reports',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'product_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'designations',
                'guard_name' => 'web',
            ],
            [
                'name' => 'shift',
                'guard_name' => 'web',
            ],
            [
                'name' => 'overtime',
                'guard_name' => 'web',
            ],
            [
                'name' => 'leave-type',
                'guard_name' => 'web',
            ],
            [
                'name' => 'leave',
                'guard_name' => 'web',
            ],
            [
                'name' => 'hrm-panel',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-agents',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customer_export',
                'guard_name' => 'web',
            ],
            [
                'name' => 'categories-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'products-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchases-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-payment-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sales-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sale-payment-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expenses-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'incomes-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'quotes-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'transfers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'returns-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'exchange-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'purchase-return-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employees-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'customers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'billers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'suppliers-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'sidebar_whatsapp',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'production-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'recipe-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'account-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'theme_settings',
                'guard_name' => 'web',
            ],
            [
                'name' => 'income-categories',
                'guard_name' => 'web',
            ],
            [
                'name' => 'expense-categories',
                'guard_name' => 'web',
            ],
            [
                'name' => 'price_edit_in_sale',
                'guard_name' => 'web',
            ],
            [
                'name' => 'cost_edit_in_products',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-dashboard',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-service-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-service-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-service-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-service-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-service-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-parts-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-parts-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-parts-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-parts-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-charges-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-payment-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-payment-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'repair-device-type',
                'guard_name' => 'web',
            ],
            [
                'name' => 'damage-stock',
                'guard_name' => 'web',
            ],
            [
                'name' => 'booking',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_project_list',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_project_add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_project_show',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_project_edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_project_delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_task_list',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_task_add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_task_show',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_task_edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_task_delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_category_list',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_category_add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_category_edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'project_category_delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'ai-assistant-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-floor',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-table',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-reservation',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-menu-type',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-modifier-group',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-kitchen',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-kitchen-dashboard',
                'guard_name' => 'web',
            ],
            [
                'name' => 'restaurant-pos',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-dashboard',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-measurements-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-measurements-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-measurements-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-measurements-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-orders-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-orders-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-orders-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-orders-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-order-payments-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-templates-index',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-templates-add',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-templates-edit',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-templates-delete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-materials-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-production-board',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-notifications-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-notifications-send',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-delivery-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-reports',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-trials-view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-trials-schedule',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-trials-complete',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-alterations-manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-alterations-approve',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-catalog.view',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring-catalog.manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring.collection.manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring.style.manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring.style_version.activate',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring.component_role.manage',
                'guard_name' => 'web',
            ],
            [
                'name' => 'tailoring.order.price_override',
                'guard_name' => 'web',
            ],
        ];

        $insertData = [];

        foreach ($permission_data as $row) {
            if (!isset($existingMap[$row['name']])) {
                $insertData[] = $row;
            }
        }

        if (!empty($insertData)) {
            DB::table('permissions')->insert($insertData);
        }
        ///permissions table data insert end///

        ///role_has_permissions table data insert start///
        $existing_role_permissions = DB::table('role_has_permissions')
            ->where('role_id', 1)
            ->pluck('permission_id')
            ->toArray();

        $existingMap = array_flip($existing_role_permissions);

        // ডাটাবেস থেকে সব পারমিশন নাম এবং আইডি নিয়ে আসা (Optimized Pluck method)
        $all_permissions_map = DB::table('permissions')->pluck('id', 'name')->toArray();
        $permissions_role = [];

        if (!config('database.connections.saleprosaas_landlord')) {
            // For salepro
            foreach ($all_permissions_map as $perm_name => $perm_id) {
                $permissions_role[] = [
                    'permission_id' => $perm_id,
                    'role_id' => 1,
                ];
            }
        } else {
            $features = $this->features();
            // ১. লজিক সেট করা: টেন্যান্ট ডাটা থাকলে প্যাকেজ ফিচারগুলো এলাউড, না থাকলে কিছুই এলাউড না (Empty)
            $allowedFeatures = !empty(self::$tenantData) ? self::$tenantData['package_features'] : [];

            $excluded_permission_names = [];
            // $features লুপ করে অপ্রয়োজনীয় পারমিশনগুলো বের করছি
            foreach ($features as $feature_key => $feature_data) {
                // লজিক: যদি ফিচারটি $allowedFeatures এর মধ্যে না থাকে, তবে বাদ যাবে।
                // ব্যাখ্যা: যদি টেন্যান্ট ডাটা না থাকে, $allowedFeatures হবে ফাঁকা।
                // ফলে in_array সবসময় false দিবে এবং সব ফিচার-পারমিশন excluded লিস্টে ঢুকে যাবে।
                if (!in_array($feature_key, $allowedFeatures)) {
                    if (!empty($feature_data['permission_names'])) {
                        $perms = explode(',', $feature_data['permission_names']);
                        foreach ($perms as $p) {
                            $excluded_permission_names[] = trim($p);
                        }
                    }
                }
            }
            // ২. মডিউল চেক করে addons হ্যান্ডেল করা
            $hasAddon = !empty(array_intersect($allowedFeatures, $this->addonList()));

            if (!$hasAddon) {
                $excluded_permission_names[] = 'addons';
            }
            // ম্যাপ থেকে চেক করে ডাটা সাজানো
            foreach ($all_permissions_map as $perm_name => $perm_id) {
                // যদি পারমিশনটির নাম excluded লিস্টে না থাকে, তবেই এটি যোগ হবে
                if (!in_array($perm_name, $excluded_permission_names)) {
                    $permissions_role[] = [
                        'permission_id' => $perm_id,
                        'role_id' => 1,
                    ];
                }
            }
        }

        $insertData = [];

        foreach ($permissions_role as $row) {
            if (!isset($existingMap[$row['permission_id']])) {
                $insertData[] = $row;
            }
        }

        if (!empty($insertData)) {
            DB::table('role_has_permissions')->insert($insertData);
        }
        ///role_has_permissions table data insert end///

        $insertedDefaultPaymentAccount = false;
        if (!DB::table('accounts')->count()) {
            DB::table('accounts')->insert([
                [
                    'id' => 1,
                    'account_no' => '019912229',
                    'name' => 'Sales Account',
                    'initial_balance' => 0.0,
                    'total_balance' => 0.0,
                    'note' => 'This is the default account.',
                    'is_default' => 1,
                    'is_active' => 1,
                    'code' => NULL,
                    'type' => 'Bank Account',
                    'parent_account_id' => NULL,
                    'is_payment' => 1,
                ],
            ]);
            $insertedDefaultPaymentAccount = true;
        }

        // The default account is inserted directly by this seeder, so preserve the same
        // mapping invariant enforced by AccountsController after accounting activation.
        if ($insertedDefaultPaymentAccount && app(AccountingModeService::class)->isDoubleEntryAuthoritative()) {
            app(PaymentAccountService::class)->ensureMapping(Account::findOrFail(1));
        }

        if (!DB::table('billers')->count()) {
            DB::table('billers')->insert([
                [
                    'id' => 1,
                    'name' => 'Test Biller',
                    'image' => NULL,
                    'company_name' => 'Test Company',
                    'vat_number' => NULL,
                    'email' => 'test@gmail.com',
                    'phone_number' => '12312',
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('brands')->count()) {
            DB::table('brands')->insert([
                [
                    'id' => 1,
                    'title' => 'Apple',
                    'image' => '20240114102326.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 2,
                    'title' => 'Samsung',
                    'image' => '20240114102343.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 3,
                    'title' => 'Huawei',
                    'image' => '20240114102512.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 4,
                    'title' => 'Xiaomi',
                    'image' => '20240114103640.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 5,
                    'title' => 'Whirlpool',
                    'image' => '20240114103701.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 6,
                    'title' => 'Nestle',
                    'image' => '20240114103717.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 7,
                    'title' => 'Kraft',
                    'image' => '20240114103851.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 8,
                    'title' => 'Kellogs',
                    'image' => '20240114103906.png',
                    'is_active' => 1,
                ],
                [
                    'id' => 9,
                    'title' => 'Fitness Brand',
                    'image' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('categories')->count()) {
            DB::table('categories')->insert([
                [
                    'id' => 1,
                    'name' => 'Smartphone & Gadgets',
                    'image' => '20260706042618.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 2,
                    'name' => 'Phone Accessories',
                    'image' => '20260706040905.jpg',
                    'parent_id' => 1,
                    'is_active' => 1,
                ],
                [
                    'id' => 3,
                    'name' => 'iPhone',
                    'image' => '20260706022049.jpeg',
                    'parent_id' => 1,
                    'is_active' => 1,
                ],
                [
                    'id' => 4,
                    'name' => 'Samsung',
                    'image' => '20260706041724.png',
                    'parent_id' => 1,
                    'is_active' => 1,
                ],
                [
                    'id' => 5,
                    'name' => 'Phone Cases',
                    'image' => '20260706041412.jpg',
                    'parent_id' => 1,
                    'is_active' => 1,
                ],
                [
                    'id' => 6,
                    'name' => 'Laptops & Computers',
                    'image' => '20260706035746.jpeg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 7,
                    'name' => 'Keyboards',
                    'image' => '20260706033355.jpg',
                    'parent_id' => 6,
                    'is_active' => 1,
                ],
                [
                    'id' => 8,
                    'name' => 'Laptop Bags',
                    'image' => '20260706035048.jpeg',
                    'parent_id' => 6,
                    'is_active' => 1,
                ],
                [
                    'id' => 9,
                    'name' => 'Mouses',
                    'image' => '20260706040701.png',
                    'parent_id' => 6,
                    'is_active' => 1,
                ],
                [
                    'id' => 10,
                    'name' => 'Webcams',
                    'image' => '20260706044323.jpg',
                    'parent_id' => 6,
                    'is_active' => 1,
                ],
                [
                    'id' => 11,
                    'name' => 'Monitors',
                    'image' => '20200701093146.jpg',
                    'parent_id' => 6,
                    'is_active' => 1,
                ],
                [
                    'id' => 12,
                    'name' => 'Smartwatches',
                    'image' => '20260706042656.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 13,
                    'name' => 'Sport Watches',
                    'image' => '20260706040324.jpeg',
                    'parent_id' => 12,
                    'is_active' => 1,
                ],
                [
                    'id' => 14,
                    'name' => 'Kids Watches',
                    'image' => '20260706034710.jpg',
                    'parent_id' => 12,
                    'is_active' => 1,
                ],
                [
                    'id' => 15,
                    'name' => 'Women Watches',
                    'image' => '20260706044227.jpg',
                    'parent_id' => 12,
                    'is_active' => 1,
                ],
                [
                    'id' => 16,
                    'name' => 'Men Watches',
                    'image' => '20260706040324.jpeg',
                    'parent_id' => 12,
                    'is_active' => 1,
                ],
                [
                    'id' => 23,
                    'name' => 'TVs, Audio & Video',
                    'image' => '20260706040602.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 24,
                    'name' => 'Television Accessories',
                    'image' => '20260706043734.jpeg',
                    'parent_id' => 23,
                    'is_active' => 1,
                ],
                [
                    'id' => 25,
                    'name' => 'HD, DVD Players',
                    'image' => '20260706021834.jpg',
                    'parent_id' => 23,
                    'is_active' => 1,
                ],
                [
                    'id' => 26,
                    'name' => 'TV-DVD Combos',
                    'image' => '20260706043925.jpg',
                    'parent_id' => 23,
                    'is_active' => 1,
                ],
                [
                    'id' => 27,
                    'name' => 'Projectors',
                    'image' => '20260706041622.jpg',
                    'parent_id' => 23,
                    'is_active' => 1,
                ],
                [
                    'id' => 28,
                    'name' => 'Projection Screen',
                    'image' => '20260706041549.jpg',
                    'parent_id' => 23,
                    'is_active' => 1,
                ],
                [
                    'id' => 29,
                    'name' => 'Fruits & Vegetables',
                    'image' => '20260706021022.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 30,
                    'name' => 'Dairy & Egg',
                    'image' => '20260706020634.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 31,
                    'name' => 'Meat & Fish',
                    'image' => '20260706040218.jpeg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 33,
                    'name' => 'Candy & Chocolates',
                    'image' => '20260706015414.jpeg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 39,
                    'name' => 'Clothing',
                    'image' => '20260706020106.jpg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 40,
                    'name' => 'Gym Accessories',
                    'image' => '20260706021541.jpeg',
                    'parent_id' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('currencies')->count()) {
            DB::table('currencies')->insert([
                [
                    'id' => 1,
                    'name' => 'US Dollar',
                    'code' => 'USD',
                    'exchange_rate' => 1.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customer_groups')->count()) {
            DB::table('customer_groups')->insert([
                [
                    'id' => 1,
                    'name' => 'General',
                    'percentage' => '0',
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('customers')->count()) {
            DB::table('customers')->insert([
                [
                    'id' => 1,
                    'customer_group_id' => 1,
                    'user_id' => NULL,
                    'name' => 'John Doe',
                    'company_name' => 'Test Company',
                    'email' => 'john@gmail.com',
                    'phone_number' => '231312',
                    'tax_no' => NULL,
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'points' => NULL,
                    'is_active' => 1,
                    'deposit' => NULL,
                    'expense' => NULL,
                ]
            ]);
        }

        if (!DB::table('pos_setting')->count()) {
            DB::table('pos_setting')->insert([
                [
                    'id' => 1,
                    'customer_id' => 1,
                    'warehouse_id' => 1,
                    'biller_id' => 1,
                    'product_number' => 2,
                    'keybord_active' => 0,
                    'is_table' => 0,
                    'send_sms' => 0,
                    'stripe_public_key' => NULL,
                    'stripe_secret_key' => NULL,
                    'paypal_live_api_username' => NULL,
                    'paypal_live_api_password' => NULL,
                    'paypal_live_api_secret' => NULL,
                    'payment_options' => 'cash,card,cheque,gift_card,deposit,paypal',
                    'invoice_option' => 'thermal',
                    'thermal_invoice_size' => '80',
                ]
            ]);
        }

        if (!DB::table('products')->count()) {
            DB::table('products')->insert([
                [
                    'id' => 1,
                    'name' => 'Zenbook 14 OLED (UX3402)｜Laptops For Home – ASUS',
                    'code' => '59028109',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1099.99,
                    'price' => 1299.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => 1,
                    'promotion_price' => '1050.99',
                    'starting_date' => '2024-01-08',
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401081146401.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 2,
                    'name' => '2021 Apple 12.9-inch iPad Pro Wi-Fi 512GB',
                    'code' => '20358923',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 3,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1000.0,
                    'price' => 1249.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => 1,
                    'promotion_price' => '1200.00',
                    'starting_date' => '2024-01-08',
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401081246041.png,202401081246062.png,202401081246063.png,202401081246064.png',
                    'file' => NULL,
                    'is_embeded' => 0,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => 0,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 3,
                    'name' => 'Apple iPhone 11 (4GB-64GB) Black',
                    'code' => '49251814',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 300.0,
                    'price' => 350.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => 1,
                    'promotion_price' => '330',
                    'starting_date' => '2024-01-08',
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401081255081.png,202401081255112.png,202401081255123.png,202401081255134.png,202401081255135.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 4,
                    'name' => 'Samsung Galaxy Chromebook Go, 14″ HD LED, Intel Celeron N4500',
                    'code' => '28090345',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 900.0,
                    'price' => 1050.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401080121221.png,202401080121242.png,202401080121243.png,202401080121254.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 5,
                    'name' => 'SAMSUNG Galaxy Book Pro 15.6 Laptop – Intel Core i5',
                    'code' => '67015642',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 950.99,
                    'price' => 1150.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401080124321.png,202401080124342.png,202401080124353.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 6,
                    'name' => 'Microsoft – Surface Laptop 4 13.5” Touch-Screen – AMD Ryzen 5',
                    'code' => '24005329',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 3,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 999.99,
                    'price' => 1111.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401080127451.png,202401080127462.png,202401080127473.jpg,202401080127484.jpg,202401080127485.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 7,
                    'name' => 'Acer Chromebook 315, 15.6 HD – Intel Celeron N4000',
                    'code' => '30798200',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 4,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 899.99,
                    'price' => 999.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401080130241.png,202401080130242.png,202401080130253.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 8,
                    'name' => 'HP Victus 16-e00244AX GTX 1650 Gaming Laptop 16.1” FHD 144Hz',
                    'code' => '81526930',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 4,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1199.0,
                    'price' => 1300.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401080134061.png,202401080134072.png,202401080134073.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 9,
                    'name' => 'Epson Inkjet WorkForce Pro WF-3820DWF',
                    'code' => '20142029',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 399.0,
                    'price' => 559.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401080141091.png,202401080141102.png,202401080141103.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 10,
                    'name' => 'iPhone 14 Pro 256GB Gold',
                    'code' => '29733132',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 990.0,
                    'price' => 1250.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401080143591.png,202401080144002.png,202401080144013.png,202401080144014.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 11,
                    'name' => 'Sony Bravia 55X90J 4K Ultra HD 55″ 140 Screen Google Smart LED TV',
                    'code' => '16530612',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 3,
                    'category_id' => 23,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 350.0,
                    'price' => 499.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 12,
                    'name' => 'Samsung 43AU7000 4K Ultra HD 43″ 109 Screen Smart LED TV',
                    'code' => '73189124',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 23,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 499.0,
                    'price' => 547.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401130357131.png,202401130357152.png,202401130357153.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 13,
                    'name' => 'Apple TV HD 32GB (2nd Generation)',
                    'code' => '71493353',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 23,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 79.0,
                    'price' => 109.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401130401491.png,202401130401522.png,202401130401533.png,202401130401544.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 14,
                    'name' => 'Apple Watch SE GPS + Cellular 40mm Space Gray',
                    'code' => '92178104',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 12,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 349.0,
                    'price' => 499.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401130410191.png,202401130410222.jpg,202401130410233.jpg',
                    'file' => NULL,
                    'is_embeded' => 0,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => 0,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                    <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 15,
                    'name' => 'Xbox One Wireless Controller Black Color',
                    'code' => '93060790',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 459.0,
                    'price' => 599.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401150808421.jpg,202401150808432.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 16,
                    'name' => 'Apple iPhone XS Max-64GB -white',
                    'code' => '22061536',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 899.0,
                    'price' => 1059.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401150814131.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 17,
                    'name' => 'Apple Watch Series 8 GPS 45mm Midnight Aluminum Case',
                    'code' => '31429623',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 12,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 399.0,
                    'price' => 499.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151009571.png,202401151009582.png,202401151009583.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 18,
                    'name' => 'Huawei Watch GT 2 Sport Stainless Steel 46mm',
                    'code' => '02456392',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 3,
                    'category_id' => 12,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 369.0,
                    'price' => 599.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => 1,
                    'promotion_price' => '499',
                    'starting_date' => '2024-01-15',
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151013061.png,202401151013062.png,202401151013073.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 19,
                    'name' => 'Samsung Galaxy Active 2 R835U Smartwatch 40mm',
                    'code' => '10203743',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 12,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 275.0,
                    'price' => 399.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151019301.png,202401151019302.png,202401151019313.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 20,
                    'name' => 'Canon EOS R10 RF-S 18-45 IS STM',
                    'code' => '13929367',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 17,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 439.0,
                    'price' => 577.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151024231.png,202401151024232.png,202401151024233.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 21,
                    'name' => 'Sony A7 III Mirrorless Camera Body Only',
                    'code' => '99421096',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 299.0,
                    'price' => 379.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202401151026581.png,202401151026592.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 22,
                    'name' => 'WOLFANG GA420 Action Camera 4K 60FPS 24MP',
                    'code' => '99218280',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 4,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 130.0,
                    'price' => 157.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151029321.png,202401151029332.jpg,202401151029343.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<div class=@item-description@>
                        <p>Quisque varius diam vel metus mattis, id aliquam diam rhoncus. Proin vitae magna in dui finibus malesuada et at nulla. Morbi elit ex, viverra vitae ante vel, blandit feugiat ligula. Fusce fermentum iaculis nibh, at sodales leo maximus a. Nullam ultricies sodales nunc, in pellentesque lorem mattis quis. Cras imperdiet est in nunc tristique lacinia. Nullam aliquam mauris eu accumsan tincidunt. Suspendisse velit ex, aliquet vel ornare vel, dignissim a tortor.</p>
                        <p>Morbi ut sapien vitae odio accumsan gravida. Morbi vitae erat auctor, eleifend nunc a, lobortis neque. Praesent aliquam dignissim viverra. Maecenas lacus odio, feugiat eu nunc sit amet, maximus sagittis dolor. Vivamus nisi sapien, elementum sit amet eros sit amet, ultricies cursus ipsum. Sed consequat luctus ligula. Curabitur laoreet rhoncus blandit. Aenean vel diam ut arcu pharetra dignissim ut sed leo. Vivamus faucibus, ipsum in vestibulum vulputate, lorem orci convallis quam, sit amet consequat nulla felis pharetra lacus. Duis semper erat mauris, sed egestas purus commodo vel.</p>
                    </div>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 23,
                    'name' => 'Fresh Organic Navel Orange',
                    'code' => '33887520',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 29,
                    'unit_id' => 4,
                    'purchase_unit_id' => 4,
                    'sale_unit_id' => 4,
                    'cost' => 2.99,
                    'price' => 3.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151115301.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Fresh Organic Navel Orange</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 24,
                    'name' => 'Banana (pack of 12)',
                    'code' => '27583341',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 29,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 0.89,
                    'price' => 1.29,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151118271.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 25,
                    'name' => 'Water Melon ~ 3KG',
                    'code' => '19186147',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 29,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 2.39,
                    'price' => 3.3,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151142511.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Water Melon ~ 3KG</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 0,
                ],
                [
                    'id' => 26,
                    'name' => 'Gala Original Apple - 1KG',
                    'code' => '80912386',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 29,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 2.39,
                    'price' => 3.19,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202401151144271.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Gala Original Apple - 1KG</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 27,
                    'name' => 'Men&#039;s Premium Egyptian Cotton T-shirt',
                    'code' => '30282941',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 16,
                    'category_id' => 39,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 50.5,
                    'price' => 70.99,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 2,
                    'image' => '202607080830062.jpeg',
                    'file' => NULL,
                    'is_embeded' => 0,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => 0,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["Size","Color"]',
                    'variant_value' => '["S,M,L,XL,XXL","red,green,blue"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 28,
                    'name' => 'Bon Sprayer',
                    'code' => '09138264',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 2,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 115.0,
                    'price' => 130.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["Color"]',
                    'variant_value' => '["Red,Yellow,Green,Bule"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 29,
                    'name' => 'Toffee',
                    'code' => '76722958',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 10.0,
                    'price' => 20.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 30,
                    'name' => 'AMD RYZEN 5 5600G',
                    'code' => '1001',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 2,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 2500.0,
                    'price' => 3500.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 31,
                    'name' => 'KINGSTON 8GB RAM',
                    'code' => '1002',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 2,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1000.0,
                    'price' => 1450.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080825182.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 32,
                    'name' => 'MI BUILD PACKAGE',
                    'code' => '1004',
                    'type' => 'combo',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 0,
                    'purchase_unit_id' => 0,
                    'sale_unit_id' => 0,
                    'cost' => 0.0,
                    'price' => 4950.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => '36,37',
                    'variant_list' => ',',
                    'qty_list' => '1,1',
                    'price_list' => '3500,1450',
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 33,
                    'name' => 'Irene Jack',
                    'code' => '3456',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1000.0,
                    'price' => 899.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 34,
                    'name' => 'Off white Tshirt',
                    'code' => '75308742',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 39,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 4.8,
                    'price' => 8.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 35,
                    'name' => 'samsung laptop',
                    'code' => '65317202',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 50000.0,
                    'price' => 55000.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 36,
                    'name' => 'samsung laptop 15',
                    'code' => '67600232',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 55000.0,
                    'price' => 60000.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 37,
                    'name' => 'TAKA',
                    'code' => '81639204',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 3000.0,
                    'price' => 3500.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 38,
                    'name' => 'Apple 14',
                    'code' => 'apple14',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 80000.0,
                    'price' => 85000.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => 10.0,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 2,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 39,
                    'name' => 'Laptop11',
                    'code' => '1111111',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 30000.0,
                    'price' => 32500.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 2.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 40,
                    'name' => 'Shirt',
                    'code' => '112233',
                    'type' => 'service',
                    'barcode_symbology' => 'C39',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 0,
                    'purchase_unit_id' => 0,
                    'sale_unit_id' => 0,
                    'cost' => 0.0,
                    'price' => 10.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 41,
                    'name' => '14 pro max',
                    'code' => '34692007',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 15000.0,
                    'price' => 16000.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080814462.jpg',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["RAM | ROM","Color"]',
                    'variant_value' => '["128GB,256GB,512GB","SpaceBlack,Silver,Gold,DeepPurple"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 42,
                    'name' => 'Iphone 15 Pro Max',
                    'code' => '63028277',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 0.0,
                    'price' => 0.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080815422.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => 1,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["Condition","RAM | ROM","Color"]',
                    'variant_value' => '["Brand New,Pre-Owned","256GB,512GB","BlackTitanium,WhiteTitanium,BlueTitanium,NaturalTitanium"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 43,
                    'name' => 'Product Test V',
                    'code' => 'KK',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 1,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 44.0,
                    'price' => 23.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => '2024-05-18',
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => 1,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["Quantity","Size","Price","Color"]',
                    'variant_value' => '["3KG,2KG,5KG","Large,Medium,Small","120,500,70","RED,GReen,Blue"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 44,
                    'name' => 'PRUEBA',
                    'code' => '000',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 9,
                    'purchase_unit_id' => 9,
                    'sale_unit_id' => 9,
                    'cost' => 7.0,
                    'price' => 7.0,
                    'wholesale_price' => 7.0,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 45,
                    'name' => 'Prueba Easy',
                    'code' => '190',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 4,
                    'purchase_unit_id' => 4,
                    'sale_unit_id' => 4,
                    'cost' => 777.0,
                    'price' => 777.0,
                    'wholesale_price' => 777.0,
                    'qty' => 0,
                    'alert_quantity' => 120.0,
                    'daily_sale_objective' => 65.0,
                    'promotion' => 1,
                    'promotion_price' => '150',
                    'starting_date' => '2024-06-11',
                    'last_date' => '2024-06-20',
                    'tax_id' => 1,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => '["BLANCO","NEGRO"]',
                    'variant_value' => '["199","299"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 46,
                    'name' => 'Producto Prueba',
                    'code' => '777',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 2,
                    'category_id' => 4,
                    'unit_id' => 4,
                    'purchase_unit_id' => 4,
                    'sale_unit_id' => 4,
                    'cost' => 200.0,
                    'price' => 200.0,
                    'wholesale_price' => 200.0,
                    'qty' => 0,
                    'alert_quantity' => 10.0,
                    'daily_sale_objective' => 10.0,
                    'promotion' => 1,
                    'promotion_price' => '175',
                    'starting_date' => NULL,
                    'last_date' => '2024-06-25',
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>Prueba de imagenes easymax</p>',
                    'variant_option' => '["NEGRO","NEGRO","NEGRO","NEGRO"]',
                    'variant_value' => '["255","255","255","255"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 47,
                    'name' => 'IPHONE 14 PRO MAX',
                    'code' => '01234',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1500.0,
                    'price' => 1500.0,
                    'wholesale_price' => 1499.0,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => 15.0,
                    'promotion' => 1,
                    'promotion_price' => '1299',
                    'starting_date' => '2024-06-11',
                    'last_date' => '2024-06-25',
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080814462.jpg',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => 1,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p><a class=@sh-anchor@ href=@https://www.bing.com/ck/a?!&amp;&amp;p=3057d02af68c6961JmltdHM9MTcxODA2NDAwMCZpZ3VpZD0zZTNlNjhiMy1jOTY5LTZkYzYtMzJjMS03Y2Q0Yzg1NjZjMDUmaW5zaWQ9NjU4Mw&amp;ptn=3&amp;ver=2&amp;hsh=3&amp;fclid=3e3e68b3-c969-6dc6-32c1-7cd4c8566c05&amp;psq=apple+descripcion&amp;u=a1aHR0cHM6Ly9odW1hbmlkYWRlcy5jb20vYXBwbGUv&amp;ntb=1@ target=@_blank@ rel=@noopener@ data-tg-citations=@1;2@ data-tgpsgid=@d_anstgsen0@>Apple es una&nbsp;<strong>empresa multinacional estadounidense que dise&ntilde;a, fabrica y vende productos electr&oacute;nicos y de software</strong></a><a class=@sup-target@ href=@https://www.bing.com/ck/a?!&amp;&amp;p=d1b09c46723a5d2aJmltdHM9MTcxODA2NDAwMCZpZ3VpZD0zZTNlNjhiMy1jOTY5LTZkYzYtMzJjMS03Y2Q0Yzg1NjZjMDUmaW5zaWQ9NjU4NA&amp;ptn=3&amp;ver=2&amp;hsh=3&amp;fclid=3e3e68b3-c969-6dc6-32c1-7cd4c8566c05&amp;psq=apple+descripcion&amp;u=a1aHR0cHM6Ly9odW1hbmlkYWRlcy5jb20vYXBwbGUv&amp;ntb=1@ target=@_blank@ rel=@noopener@ data-tgpsgid=@d_anstgpsg1@><sup>1</sup></a><a class=@sup-target@ href=@https://www.bing.com/ck/a?!&amp;&amp;p=21491c7be3d7d5d9JmltdHM9MTcxODA2NDAwMCZpZ3VpZD0zZTNlNjhiMy1jOTY5LTZkYzYtMzJjMS03Y2Q0Yzg1NjZjMDUmaW5zaWQ9NjU4NQ&amp;ptn=3&amp;ver=2&amp;hsh=3&amp;fclid=3e3e68b3-c969-6dc6-32c1-7cd4c8566c05&amp;psq=apple+descripcion&amp;u=a1aHR0cHM6Ly93d3cuMTJjYXJhY3RlcmlzdGljYXMuY29tL2FwcGxlLw&amp;ntb=1@ target=@_blank@ rel=@noopener@ data-tgpsgid=@d_anstgpsg2@><sup>2</sup></a>.&nbsp;<a class=@sh-anchor@ href=@https://www.bing.com/ck/a?!&amp;&amp;p=c80ea6db0a534e3eJmltdHM9MTcxODA2NDAwMCZpZ3VpZD0zZTNlNjhiMy1jOTY5LTZkYzYtMzJjMS03Y2Q0Yzg1NjZjMDUmaW5zaWQ9NjU4Ng&amp;ptn=3&amp;ver=2&amp;hsh=3&amp;fclid=3e3e68b3-c969-6dc6-32c1-7cd4c8566c05&amp;psq=apple+descripcion&amp;u=a1aHR0cHM6Ly93d3cuMTJjYXJhY3RlcmlzdGljYXMuY29tL2FwcGxlLw&amp;ntb=1@ target=@_blank@ rel=@noopener@ data-tg-citations=@2@ data-tgpsgid=@d_anstgsen1@>Entre sus productos m&aacute;s conocidos se encuentran el iPhone, el iPad, el Mac, el iPod, el Apple Watch y el Apple TV. Tambi&eacute;n ofrece servicios en l&iacute;nea como iTunes, iCloud, Apple Music y Apple Pay. Apple tiene su sede en el Apple Park, en Cupertino, California, y su centro europeo en Cork, Irlanda</a></p>',
                    'variant_option' => '["Color Blanco","Color Rosa","RAM","Almacenamiento","Color Blanco","Color Rosa","RAM","Almacenamiento","Color Blanco","Color Rosa","RAM","Almacenamiento"]',
                    'variant_value' => '["1600","1699","8,16,32","32,64,128","1600","1699","8,16,32","32,64,128","1600","1699","8,16,32","32,64,128"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 48,
                    'name' => 'T-Shirt',
                    'code' => '003',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => NULL,
                    'category_id' => 4,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 8000.0,
                    'price' => 9500.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 3.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => '2024-06-21',
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080830062.jpeg',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 49,
                    'name' => 'Laptop',
                    'code' => '83058761',
                    'type' => 'standard',
                    'barcode_symbology' => 'C39',
                    'brand_id' => 2,
                    'category_id' => 6,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 1000.0,
                    'price' => 2000.0,
                    'wholesale_price' => 500.0,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 50,
                    'name' => 'Glass',
                    'code' => '37580174',
                    'type' => 'standard',
                    'barcode_symbology' => 'UPCA',
                    'brand_id' => 10,
                    'category_id' => 4,
                    'unit_id' => 4,
                    'purchase_unit_id' => 4,
                    'sale_unit_id' => 5,
                    'cost' => 70.0,
                    'price' => 100.0,
                    'wholesale_price' => 60.0,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => 3.0,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080742312.jpg',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 51,
                    'name' => 'Test Prod V',
                    'code' => '862837',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 100.0,
                    'price' => 150.0,
                    'wholesale_price' => 120.0,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 3,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => 1,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<p>test desc</p>',
                    'variant_option' => '["Size","Colour"]',
                    'variant_value' => '["S,M,L","R,g,b"]',
                    'is_active' => 1,
                ],
                [
                    'id' => 52,
                    'name' => 'Earphone True Wireless G70',
                    'code' => '2312021280054',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 58.0,
                    'price' => 17.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => NULL,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => '202607080812502.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '<h1 style=@text-align: right;@>هیدفۆنی وایەرلێس جی٧٠</h1>
                    <p style=@text-align: right;@>&nbsp;</p>
                    <h2 style=@text-align: right;@>تایبەتمەندیەکانی</h2>
                    <p style=@text-align: right;@>هیدفۆنی بێ وایەر</p>
                    <p style=@text-align: right;@>سیستەمی گونجاو :&nbsp;ئەندرۆید / ئی ئۆ ئێس / ویندۆس</p>
                    <p style=@text-align: right;@>وەشانی بلوتوز :&nbsp;٥.٣</p>
                    <p style=@text-align: right;@>توانای پاتری :&nbsp;٣٠ میلی ئەمپێر</p>
                    <p style=@text-align: right;@>توانای پاتری سندوقی شەحنکردنەوە :&nbsp;٢٥٠ میلی ئەمپێر</p>
                    <p style=@text-align: right;@>شێوازی شەحنکردنەوە :&nbsp;شەحنکردنەوەی جۆری سی</p>
                    <p style=@text-align: right;@>تەمەنی پاتری : نزیکەی ٤ بۆ ٥ کاتژمێر</p>
                    <p style=@text-align: right;@>&nbsp;</p>
                    <h1>Earphone True Wireless G70</h1>
                    <p style=@text-align: justify;@>&nbsp;</p>
                    <h2>Specification</h2>
                    <p>Product Type :&nbsp;Wireless Earbuds</p>
                    <p>Brand :&nbsp;UiiSii</p>
                    <p>Model :&nbsp;TWS-G70</p>
                    <p>Compatible Systems :&nbsp;ios/android/Windows</p>
                    <p>Bluetooth Version :&nbsp;5.3</p>
                    <p>Battery Capacity :&nbsp;30 mAh</p>
                    <p>Charging Box Battery Capacity :&nbsp;250 mAh</p>
                    <p>Charging Method :&nbsp;TYPE-C charging</p>
                    <p>Buds Battery Life :&nbsp;About 4 to 5 hours</p>
                    <p>&nbsp;</p>',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 53,
                    'name' => 'T shirt',
                    'code' => '07116185',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 1,
                    'category_id' => 3,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 500.0,
                    'price' => 1000.0,
                    'wholesale_price' => 700.0,
                    'qty' => 0,
                    'alert_quantity' => 100.0,
                    'daily_sale_objective' => 100.0,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => '2024-10-12',
                    'last_date' => NULL,
                    'tax_id' => NULL,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => 1,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => NULL,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => '',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 56,
                    'name' => 'Dumbbell',
                    'code' => 'gym001',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 9,
                    'category_id' => 40,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 20.0,
                    'price' => 30.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 10.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => 'High quality rubber coated dumbbells.',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 57,
                    'name' => 'Twister',
                    'code' => 'gym002',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 9,
                    'category_id' => 40,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 15.0,
                    'price' => 25.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => 'Effective waist twister for core workout.',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 58,
                    'name' => 'Ab Bench',
                    'code' => 'gym003',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 9,
                    'category_id' => 40,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 50.0,
                    'price' => 80.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 5.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => 'Adjustable ab bench for core exercises.',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ],
                [
                    'id' => 59,
                    'name' => 'Treadmill',
                    'code' => 'gym004',
                    'type' => 'standard',
                    'barcode_symbology' => 'C128',
                    'brand_id' => 9,
                    'category_id' => 40,
                    'unit_id' => 1,
                    'purchase_unit_id' => 1,
                    'sale_unit_id' => 1,
                    'cost' => 500.0,
                    'price' => 800.0,
                    'wholesale_price' => NULL,
                    'qty' => 0,
                    'alert_quantity' => 2.0,
                    'daily_sale_objective' => NULL,
                    'promotion' => NULL,
                    'promotion_price' => NULL,
                    'starting_date' => NULL,
                    'last_date' => NULL,
                    'tax_id' => 1,
                    'tax_method' => 1,
                    'image' => 'zummXD2dvAtI.png',
                    'file' => NULL,
                    'is_embeded' => NULL,
                    'is_variant' => NULL,
                    'is_batch' => NULL,
                    'is_diffPrice' => NULL,
                    'is_imei' => NULL,
                    'featured' => 1,
                    'product_list' => NULL,
                    'variant_list' => NULL,
                    'qty_list' => NULL,
                    'price_list' => NULL,
                    'product_details' => 'Electric treadmill with multiple speed settings.',
                    'variant_option' => NULL,
                    'variant_value' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('suppliers')->count()) {
            DB::table('suppliers')->insert([
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'image' => NULL,
                    'company_name' => 'Test Company',
                    'vat_number' => NULL,
                    'email' => 'john@gmail.com',
                    'phone_number' => '231312',
                    'address' => 'Test address',
                    'city' => 'Test City',
                    'state' => NULL,
                    'postal_code' => NULL,
                    'country' => NULL,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('taxes')->count()) {
            DB::table('taxes')->insert([
                [
                    'id' => 1,
                    'name' => 'VAT 10%',
                    'rate' => 10.0,
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('units')->count()) {
            DB::table('units')->insert([
                [
                    'id' => 1,
                    'unit_code' => 'Pc',
                    'unit_name' => 'piece',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ],
                [
                    'id' => 4,
                    'unit_code' => 'ltr',
                    'unit_name' => 'Liter',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ],
                [
                    'id' => 5,
                    'unit_code' => 'm',
                    'unit_name' => 'Meter',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ],
                [
                    'id' => 9,
                    'unit_code' => 'set',
                    'unit_name' => 'Set',
                    'base_unit' => NULL,
                    'operator' => '*',
                    'operation_value' => 1.0,
                    'is_active' => 1,
                ],
            ]);
        }

        if (!DB::table('warehouses')->count()) {
            DB::table('warehouses')->insert([
                [
                    'id' => 1,
                    'name' => 'Test Shop',
                    'phone' => '9991111',
                    'email' => NULL,
                    'address' => 'Test address',
                    'is_active' => 1,
                ]
            ]);
        }

        if (!DB::table('notification_settings')->count()) {
            DB::table('notification_settings')->insert([
                [
                    'event' => 'sale_created',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => 'Hello [customer], your order [reference] of [amount] has been created successfully!',
                    'sms_message' => 'Order [reference] confirmed. Total: [amount]. Thank you!',
                    'mail_message' => 'Dear [customer],<br><br>Thank you for your order. Your transaction reference [reference] for the total amount of [amount] has been created successfully.<br><br>Best Regards,<br>Management',
                    'recipients' => json_encode(['admin', 'customer']), // 👈 Updated to JSON string representation
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'purchase_created',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => 'New Purchase Order created: [reference].',
                    'sms_message' => 'Purchase [reference] logged in system.',
                    'mail_message' => 'Hello Admin,<br><br>A new Purchase Order has been processed with Reference ID: <strong>[reference]</strong>.<br><br>Please check your backend inventory panel for details.',
                    'recipients' => json_encode(['admin']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'low_stock',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => '⚠️ ALERT: The following items are running low on stock: [product]',
                    'sms_message' => 'Low Stock Alert: [product]',
                    'mail_message' => 'System Alert Notice:<br><br>The following inventory items have fallen below their configured safety minimum metrics:<br><br><strong>[product]</strong><br><br>Please coordinate with warehouse teams for restocking operations.',
                    'recipients' => json_encode(['admin', 'supplier']), // 👈 Both now assigned out of the box
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'payment_received',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => 'Hi [customer], we received your payment for [reference]. Balance updated.',
                    'sms_message' => 'Payment received for [reference]. Amount: [amount].',
                    'mail_message' => 'Dear [customer],<br><br>We have successfully recognized your payment submission of [amount] toward bill reference balance record: [reference]. Your ledger balance parameters have been shifted accordingly.',
                    'recipients' => json_encode(['admin', 'customer']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'quotation_created',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => 'Hello [customer], your quotation [reference] is ready for review.',
                    'sms_message' => 'Quotation [reference] has been sent.',
                    'mail_message' => 'Dear [customer],<br><br>Your custom quotation ledger sheet has been computed. Review document details linked with system request reference token: <strong>[reference]</strong>.<br><br>Let us know if you have any questions.',
                    'recipients' => json_encode(['customer']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'expiry_alert',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => '⏳ EXPIRY NOTICE: The following items expire soon: [product]',
                    'sms_message' => 'Product Expiry Alert: [product]',
                    'mail_message' => 'Automated Cron Maintenance Log:<br><br>Attention: The following product item groups are reaching their expiration threshold parameters:<br><br>[product]<br><br>Please implement swift rotational clearings.',
                    'recipients' => json_encode(['admin']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'event' => 'stock_transfer',
                    'notify_in_app' => 1,
                    'notify_whatsapp' => 0,
                    'notify_sms' => 0,
                    'notify_mail' => 0,
                    'whatsapp_message' => 'Stock transfer [reference] has been initiated.',
                    'sms_message' => 'Transfer [reference] dispatched.',
                    'mail_message' => 'Internal Transfer Route Alert:<br><br>Stock movement assignment ledger [reference] has shifted status to dispatched or initialized. Verify upon localized arrival routing.',
                    'recipients' => json_encode(['admin']),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }
    }
}
