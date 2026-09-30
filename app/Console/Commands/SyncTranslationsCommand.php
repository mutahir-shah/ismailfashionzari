<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:sync {--locale= : Specific locale to sync (defaults to all locale files)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Non-destructively synchronize missing translation keys from seed files into the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!Schema::hasTable('translations')) {
            $this->warn('Translations table does not exist. Skipping synchronization.');
            return 0;
        }

        $specificLocale = $this->option('locale');

        $existing = DB::table('translations')
            ->select('locale', 'group', 'key')
            ->get();

        $existingMap = [];
        foreach ($existing as $item) {
            $group = $item->group ?? 'db';
            $existingMap[$item->locale . '|' . $group . '|' . $item->key] = true;
        }

        $directory = database_path('seeders/Tenant/translations');
        $files = glob($directory . '/*.php');

        $totalInserted = 0;
        $insertData = [];

        foreach ($files as $file) {
            $locale = basename($file, '.php');
            if ($specificLocale && $locale !== $specificLocale) {
                continue;
            }

            $data = include $file;
            if (!is_array($data)) {
                continue;
            }

            $localeInserted = 0;
            foreach ($data as $row) {
                if (!isset($row['key']) || !isset($row['value'])) {
                    continue;
                }

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
                    $localeInserted++;
                    $totalInserted++;
                }
            }

            if ($localeInserted > 0) {
                $this->info("Prepared {$localeInserted} new keys for locale '{$locale}'.");
            }
        }

        if (!empty($insertData)) {
            $chunks = collect($insertData)->chunk(1000);
            foreach ($chunks as $chunk) {
                DB::table('translations')->insert($chunk->toArray());
            }
        }

        Translation::forgetCachedTranslations();

        $this->info("Translation synchronization complete. Total new keys inserted: {$totalInserted}.");

        return 0;
    }
}
