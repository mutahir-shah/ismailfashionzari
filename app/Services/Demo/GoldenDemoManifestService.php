<?php

namespace App\Services\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Canonical persistence boundary for Golden Demo evidence.
 *
 * The manifest is evidence, not a cache. Writes therefore validate the owning
 * database, preserve omitted keys, protect the baseline, and replace the file
 * atomically only after the replacement JSON has been read back successfully.
 */
class GoldenDemoManifestService
{
    public function path(): string
    {
        return config('salepro.golden_demo_manifest_path')
            ?: base_path('tests/Demo/expected/golden_manifest.json');
    }

    public function exists(): bool
    {
        return File::exists($this->path());
    }

    public function read(): array
    {
        if (!$this->exists()) {
            throw new RuntimeException('Golden Demo manifest does not exist.');
        }

        $manifest = json_decode(File::get($this->path()), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            throw new RuntimeException('Golden Demo manifest root must be an object.');
        }
        if (!isset($manifest['version']) || !is_string($manifest['version'])) {
            throw new RuntimeException('Golden Demo manifest version is missing or malformed.');
        }
        if (isset($manifest['manifest_schema_version'])
            && !in_array((int) $manifest['manifest_schema_version'], [2, 3], true)) {
            throw new RuntimeException('Unsupported Golden Demo manifest schema version.');
        }

        return $manifest;
    }

    /**
     * Start evidence for a clean build. This is the only operation allowed to
     * replace evidence belonging to a different database.
     */
    public function start(array $manifest): array
    {
        $database = DB::connection()->getDatabaseName();
        $declared = $manifest['database_identifier'] ?? null;
        if (!$declared || $declared !== $database) {
            throw new RuntimeException("New Golden Demo manifest must identify the active database [{$database}].");
        }

        if (DB::table('sales')->exists() || DB::table('purchases')->exists()) {
            throw new RuntimeException('Refusing to start Golden Demo evidence after business transactions already exist.');
        }

        $manifest['manifest_schema_version'] = $manifest['manifest_schema_version'] ?? 3;
        $manifest['created_at'] = $manifest['created_at'] ?? now()->toIso8601String();
        $manifest['environment'] = app()->environment();
        $manifest['build_identifier'] = $manifest['build_identifier']
            ?? hash('sha256', $database.'|'.$manifest['created_at']);
        $this->atomicWrite($manifest);

        return $manifest;
    }

    /**
     * Merge phase evidence without permitting an omitted key to disappear.
     */
    public function merge(array $evidence): array
    {
        $manifest = $this->read();
        $this->assertOwnedByActiveDatabase($manifest);

        if (array_key_exists('database_identifier', $evidence)
            && $evidence['database_identifier'] !== $manifest['database_identifier']) {
            throw new RuntimeException('Refusing to merge Golden Demo evidence from another database.');
        }

        if (isset($manifest['baseline'], $evidence['baseline'])
            && $manifest['baseline'] !== $evidence['baseline']) {
            throw new RuntimeException('Golden Demo baseline evidence is immutable after manifest creation.');
        }
        if (isset($manifest['catalog_baseline'], $evidence['catalog_baseline'])
            && $manifest['catalog_baseline'] !== $evidence['catalog_baseline']) {
            throw new RuntimeException('Golden Demo catalog baseline evidence is immutable after manifest creation.');
        }

        $this->assertPhaseOrdering($manifest, $evidence);
        $this->assertCertifiedEvidenceUnchanged($manifest, $evidence);

        $merged = $this->mergeRecursivePreservingLists($manifest, $evidence);
        $merged['manifest_schema_version'] = $manifest['manifest_schema_version'] ?? 2;
        $merged['database_identifier'] = $manifest['database_identifier'];
        $merged['updated_at'] = now()->toIso8601String();
        if (isset($merged['masters_created'])) {
            $captured = app(GoldenDemoEvidenceService::class)->capture($merged);
            $merged = $this->mergeRecursivePreservingLists($merged, $captured);
        }
        if (($merged['phase16_validation']['passed'] ?? false) === true) {
            $merged['certified_at'] = $merged['certified_at'] ?? now()->toIso8601String();
        }
        $this->atomicWrite($merged);

        return $merged;
    }

    public function assertOwnedByActiveDatabase(?array $manifest = null): void
    {
        $manifest ??= $this->read();
        $active = DB::connection()->getDatabaseName();
        $owner = $manifest['database_identifier'] ?? null;

        if (!$owner || $owner !== $active) {
            throw new RuntimeException("Golden Demo manifest belongs to [{$owner}], not active database [{$active}].");
        }
    }

    private function mergeRecursivePreservingLists(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (is_array($value)
                && isset($existing[$key])
                && is_array($existing[$key])
                && !array_is_list($value)
                && !array_is_list($existing[$key])) {
                $existing[$key] = $this->mergeRecursivePreservingLists($existing[$key], $value);
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    private function assertPhaseOrdering(array $manifest, array $evidence): void
    {
        if (!isset($evidence['phases_completed'])) {
            return;
        }

        $existing = array_values($manifest['phases_completed'] ?? []);
        $incoming = array_values($evidence['phases_completed']);
        if (array_slice($incoming, 0, count($existing)) !== $existing) {
            throw new RuntimeException('Golden Demo phases may only be appended in order.');
        }

        foreach ($incoming as $index => $phase) {
            if ($phase !== 'Phase '.($index + 1)) {
                throw new RuntimeException("Golden Demo phase ordering is invalid at [{$phase}].");
            }
        }
    }

    private function assertCertifiedEvidenceUnchanged(array $manifest, array $evidence): void
    {
        foreach ($evidence as $key => $value) {
            if (str_ends_with((string) $key, '_checkpoint')
                && array_key_exists($key, $manifest)
                && $manifest[$key] !== $value) {
                throw new RuntimeException("Certified Golden Demo checkpoint [{$key}] is immutable.");
            }
        }

        $phaseEvidence = [
            3 => ['masters_created', 'cash_register'],
            4 => ['purchases_created', 'payments_created'],
            5 => ['transfers_created'],
            6 => ['adjustments_created'],
            7 => ['sales_created'],
            8 => ['payment_mutations'],
            9 => ['returns_created'],
            10 => ['exchanges_created'],
            11 => ['purchase_mutations'],
            12 => ['purchase_returns_created'],
            13 => ['restaurant_sales_created'],
            14 => ['phase14'],
            15 => ['phase15'],
            16 => ['phase16'],
        ];

        foreach ($phaseEvidence as $phase => $keys) {
            if (($manifest["phase{$phase}_validation"]['passed'] ?? false) !== true) {
                continue;
            }
            foreach ($keys as $key) {
                if (array_key_exists($key, $manifest)
                    && array_key_exists($key, $evidence)
                    && $manifest[$key] !== $evidence[$key]) {
                    throw new RuntimeException("Certified Golden Demo scenario evidence [{$key}] is immutable.");
                }
            }
        }
    }

    private function atomicWrite(array $manifest): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));

        $json = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        ).PHP_EOL;
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(8));

        try {
            File::put($temporary, $json, true);
            $verified = json_decode(File::get($temporary), true, 512, JSON_THROW_ON_ERROR);
            if ($verified !== $manifest) {
                throw new RuntimeException('Golden Demo manifest atomic-write verification failed.');
            }

            if (File::exists($path)) {
                File::copy($path, $path.'.previous');
            }

            if (PHP_OS_FAMILY === 'Windows') {
                // Windows does not permit POSIX-style rename-over-existing.
                // Promote the already verified same-directory file, with the
                // previous complete JSON retained for immediate rollback.
                if (File::exists($path) && !@unlink($path)) {
                    throw new RuntimeException('Unable to release the prior Golden Demo manifest on Windows.');
                }
                if (!@rename($temporary, $path)) {
                    if (File::exists($path.'.previous')) {
                        File::copy($path.'.previous', $path);
                    }
                    throw new RuntimeException('Unable to promote the verified Golden Demo manifest on Windows.');
                }
            } else {
                // Illuminate's replace() uses a same-directory temporary file
                // and atomic rename on POSIX filesystems.
                File::replace($path, $json);
            }
        } finally {
            if (File::exists($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
