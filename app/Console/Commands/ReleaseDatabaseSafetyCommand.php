<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReleaseDatabaseSafetyCommand extends Command
{
    protected $signature = 'salepro:release-db-safety {--expected=salepro_testing : Exact dedicated database allowed for certification}';

    protected $description = 'Fail closed unless the current Laravel runtime uses the dedicated SalePro testing database';

    public function handle(): int
    {
        $expected = trim((string) $this->option('expected'));
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $normalDatabase = $this->normalEnvironmentDatabase();
        $errors = [];

        if (! app()->environment('testing')) {
            $errors[] = 'APP_ENV is not testing.';
        }
        if ($database === '' || $database !== $expected) {
            $errors[] = "Resolved database [{$database}] is not the explicitly allowed testing database [{$expected}].";
        }
        if (! preg_match('/(^|_)test(ing)?($|_)/i', $database)) {
            $errors[] = 'Resolved database name is not recognizably test-only.';
        }
        if (preg_match('/(demo|golden|prod|production)/i', $database)) {
            $errors[] = 'Resolved database name matches a protected database class.';
        }
        if ($normalDatabase !== null && $normalDatabase === $database) {
            $errors[] = 'Resolved testing database matches the normal .env database.';
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info("SAFE: APP_ENV=testing, connection={$connection}, database={$database}; normal .env database differs.");

        return self::SUCCESS;
    }

    private function normalEnvironmentDatabase(): ?string
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false || ! preg_match('/^DB_DATABASE=(.*)$/m', $contents, $matches)) {
            return null;
        }

        return trim(trim($matches[1]), "\"'");
    }
}
