<?php

namespace App\Services\Demo;

use App\Http\Controllers\ProductController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Product_Warehouse;
use App\Models\Unit;
use App\Models\Variant;
use App\Services\ProductImportImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class GoldenDemoCatalogService
{
    public const SOURCE_FILES = [
        'products' => [
            'name' => 'salepro_demo_products_import.csv',
            'sha256' => '755D0D9FA1FA33B3465D0DB2BD4719C3DF33FEB49C2E74D5EDF23D08486F17E1',
        ],
        'categories' => [
            'name' => 'salepro_demo_categories_import.csv',
            'sha256' => '046043E4425F9EE83C7B4023D0ED6E1CFCCEBC37D92F2F4C4EDC310825F9CC57',
        ],
        'brands' => [
            'name' => 'salepro_demo_brands_import.csv',
            'sha256' => 'D9F1E66E4B0401657A3A96248BE2125C4DFD161531180E3E37EFDFE1B35AB48B',
        ],
        'units' => [
            'name' => 'salepro_demo_units_import.csv',
            'sha256' => '92BC9555A25C19B39012B1118C5533BBD72E4A71566F2FB60323C12B8B1F2481',
        ],
    ];

    public function prepareForPhase1(?string $sourceDirectory = null): array
    {
        if (app()->runningUnitTests() && !$sourceDirectory && !env('DEMO_CATALOG_SOURCE_DIR')) {
            return $this->captureBaseline(
                Product::where('is_active', true)->pluck('code')->all(),
                ['kind' => 'isolated_test_fixture'],
                false
            );
        }

        $sourceDirectory ??= (string) env('DEMO_CATALOG_SOURCE_DIR', '');
        if ($sourceDirectory === '') {
            throw new RuntimeException('DEMO_CATALOG_SOURCE_DIR is required for a Master Golden Demo build.');
        }

        return $this->installAuthoritativeCatalog($sourceDirectory);
    }

    public function installAuthoritativeCatalog(string $sourceDirectory): array
    {
        GoldenDemoSafety::assertExactDemoDatabase();
        $this->assertNoBusinessData();
        $sources = $this->verifySources($sourceDirectory);
        $productRows = $this->readCsv($sources['products']['path']);
        $expectedCodes = array_values(array_map(fn (array $row) => trim((string) $row['code']), $productRows));

        if (count($expectedCodes) !== count(array_unique(array_map('strtolower', $expectedCodes)))) {
            throw new RuntimeException('Authoritative catalog contains duplicate product codes.');
        }

        DB::transaction(function () use ($sources, $productRows): void {
            $this->replaceSeedCatalog();
            $this->importUnits($this->readCsv($sources['units']['path']));
            $this->importCategories($this->readCsv($sources['categories']['path']));
            $this->importBrands($this->readCsv($sources['brands']['path']));
            $this->importProducts($productRows);
        });

        $this->processProductImages($productRows);

        $baseline = $this->captureBaseline($expectedCodes, [
            'kind' => 'authoritative_csv_set',
            'files' => collect($sources)->map(fn (array $source) => [
                'filename' => basename($source['path']),
                'sha256' => $source['sha256'],
            ])->all(),
        ], true);
        $validation = $this->validateCompleteness($baseline);
        if (!$validation['passed']) {
            throw new RuntimeException('Catalog completeness gate failed before Phase 1: '.json_encode($validation['failures']));
        }

        return $baseline;
    }

    public function captureBaseline(array $expectedCodes, array $source, bool $requireImages): array
    {
        $expectedCodes = array_values(array_unique(array_map('strval', $expectedCodes)));
        sort($expectedCodes, SORT_STRING);
        $records = $this->stableRecords($expectedCodes);
        $categories = Category::where('is_active', true)->orderBy('name')->pluck('name')->all();
        $brands = Brand::where('is_active', true)->orderBy('title')->pluck('title')->all();
        $units = Unit::where('is_active', true)->orderBy('unit_code')->pluck('unit_code')->all();
        $warehouses = DB::table('warehouses')->where('is_active', true)->orderBy('name')->pluck('name')->all();
        $images = $this->imageEvidence($expectedCodes, $requireImages);
        $typeCounts = [];
        foreach ($records as $record) {
            $typeCounts[$record['type']] = ($typeCounts[$record['type']] ?? 0) + 1;
        }
        ksort($typeCounts);

        return [
            'captured_at' => now()->toIso8601String(),
            'database_identifier' => DB::connection()->getDatabaseName(),
            'source' => $source,
            'product_count' => count($expectedCodes),
            'active_product_count' => count($records),
            'product_type_counts' => $typeCounts,
            'variant_parent_count' => count(array_filter($records, fn (array $record) => $record['is_variant'])),
            'variant_row_count' => array_sum(array_map(fn (array $record) => count($record['variants']), $records)),
            'category_count' => count($categories),
            'brand_count' => count($brands),
            'unit_count' => count($units),
            'warehouse_count' => count($warehouses),
            'expected_codes' => $expectedCodes,
            'expected_code_hash' => hash('sha256', implode("\n", $expectedCodes)),
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'warehouses' => $warehouses,
            'stable_records' => $records,
            'fingerprint_method' => 'sha256(canonical-json(code,name,type,category,brand,unit,is_variant,is_active,ordered variants))',
            'fingerprint' => $this->fingerprint($records),
            'images' => $images,
        ];
    }

    public function validateCompleteness(array $baseline): array
    {
        $expectedCodes = array_values($baseline['expected_codes'] ?? []);
        $actualRecords = $this->stableRecords($expectedCodes);
        $actualByCode = collect($actualRecords)->keyBy('code');
        $expectedByCode = collect($baseline['stable_records'] ?? [])->keyBy('code');
        $missing = array_values(array_filter($expectedCodes, fn (string $code) => !$actualByCode->has($code)));
        $deactivated = Product::whereIn('code', $expectedCodes)->where('is_active', '!=', true)->pluck('code')->unique()->values()->all();
        $duplicateCodes = DB::table('products')->select('code')->whereIn('code', $expectedCodes)
            ->groupBy('code')->havingRaw('COUNT(*) > 1')->pluck('code')->all();
        $changed = [];
        foreach ($expectedByCode as $code => $expected) {
            if ($actualByCode->has($code) && $actualByCode->get($code) !== $expected) {
                $changed[] = (string) $code;
            }
        }

        $missingCategories = array_values(array_diff($baseline['categories'] ?? [], Category::where('is_active', true)->pluck('name')->all()));
        $missingBrands = array_values(array_diff($baseline['brands'] ?? [], Brand::where('is_active', true)->pluck('title')->all()));
        $missingUnits = array_values(array_diff($baseline['units'] ?? [], Unit::where('is_active', true)->pluck('unit_code')->all()));
        $missingWarehouses = array_values(array_diff($baseline['warehouses'] ?? [], DB::table('warehouses')->where('is_active', true)->pluck('name')->all()));
        $images = $this->imageEvidence($expectedCodes, (bool) ($baseline['images']['required'] ?? false));
        $failures = [];
        if ($missing) $failures[] = 'Missing catalog products: '.implode(', ', $missing).'.';
        if ($deactivated) $failures[] = 'Deactivated catalog products: '.implode(', ', $deactivated).'.';
        if ($duplicateCodes) $failures[] = 'Duplicate catalog product codes: '.implode(', ', $duplicateCodes).'.';
        if ($changed) $failures[] = 'Catalog fingerprint members changed: '.implode(', ', $changed).'.';
        if ($missingCategories) $failures[] = 'Missing catalog categories: '.implode(', ', $missingCategories).'.';
        if ($missingBrands) $failures[] = 'Missing catalog brands: '.implode(', ', $missingBrands).'.';
        if ($missingUnits) $failures[] = 'Missing catalog units: '.implode(', ', $missingUnits).'.';
        if ($missingWarehouses) $failures[] = 'Missing catalog warehouses: '.implode(', ', $missingWarehouses).'.';
        if ($images['broken_references']) $failures[] = 'Broken catalog image references: '.count($images['broken_references']).'.';

        return [
            'passed' => $failures === [],
            'expected_products' => count($expectedCodes),
            'actual_products' => count($actualRecords),
            'missing_products' => $missing,
            'deactivated_products' => $deactivated,
            'duplicate_codes' => $duplicateCodes,
            'changed_products' => $changed,
            'expected_fingerprint' => $baseline['fingerprint'] ?? null,
            'actual_fingerprint' => $this->fingerprint($actualRecords),
            'fingerprint_match' => isset($baseline['fingerprint']) && hash_equals((string) $baseline['fingerprint'], $this->fingerprint($actualRecords)),
            'expected_variants' => (int) ($baseline['variant_row_count'] ?? 0),
            'actual_variants' => array_sum(array_map(fn (array $record) => count($record['variants']), $actualRecords)),
            'missing_categories' => $missingCategories,
            'missing_brands' => $missingBrands,
            'missing_units' => $missingUnits,
            'missing_warehouses' => $missingWarehouses,
            'images' => $images,
            'failures' => $failures,
        ];
    }

    private function verifySources(string $sourceDirectory): array
    {
        $verified = [];
        foreach (self::SOURCE_FILES as $key => $definition) {
            $path = rtrim($sourceDirectory, '\\/').DIRECTORY_SEPARATOR.$definition['name'];
            if (!is_file($path)) {
                throw new RuntimeException("Authoritative catalog source is missing: {$definition['name']}");
            }
            $hash = strtoupper((string) hash_file('sha256', $path));
            if (!hash_equals($definition['sha256'], $hash)) {
                throw new RuntimeException("Authoritative catalog source hash mismatch: {$definition['name']}");
            }
            $verified[$key] = ['path' => $path, 'sha256' => $hash];
        }
        return $verified;
    }

    private function assertNoBusinessData(): void
    {
        foreach (['sales', 'purchases', 'transfers', 'adjustments', 'returns', 'return_purchases', 'sale_exchanges', 'productions', 'service_jobs'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Catalog installation requires a transaction-free database; [{$table}] is not empty.");
            }
        }
    }

    private function replaceSeedCatalog(): void
    {
        foreach (['product_warehouse', 'product_variants', 'variants', 'products', 'categories', 'brands', 'units'] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->delete();
        }
    }

    private function importUnits(array $rows): void
    {
        $pending = $rows;
        do {
            $progress = false;
            foreach ($pending as $index => $row) {
                $baseCode = trim((string) ($row['base_unit'] ?? ''));
                $base = $baseCode === '' ? null : Unit::where('unit_code', $baseCode)->first();
                if ($baseCode !== '' && !$base) continue;
                Unit::create([
                    'unit_code' => trim((string) $row['code']),
                    'unit_name' => trim((string) $row['name']),
                    'base_unit' => $base?->id,
                    'operator' => trim((string) ($row['operator'] ?? '')) ?: '*',
                    'operation_value' => (float) (($row['operation_value'] ?? '') !== '' ? $row['operation_value'] : 1),
                    'is_active' => true,
                ]);
                unset($pending[$index]);
                $progress = true;
            }
        } while ($pending && $progress);
        if ($pending) throw new RuntimeException('Catalog units contain unresolved base-unit references.');
    }

    private function importCategories(array $rows): void
    {
        $pending = $rows;
        do {
            $progress = false;
            foreach ($pending as $index => $row) {
                $parentName = trim((string) ($row['parent_category'] ?? ''));
                $parent = $parentName === '' ? null : Category::where('name', $parentName)->first();
                if ($parentName !== '' && !$parent) continue;
                Category::create([
                    'name' => trim((string) $row['name']),
                    'parent_id' => $parent?->id,
                    'slug' => Str::slug((string) $row['name']),
                    'is_active' => true,
                ]);
                unset($pending[$index]);
                $progress = true;
            }
        } while ($pending && $progress);
        if ($pending) throw new RuntimeException('Catalog categories contain unresolved parent references.');
    }

    private function importBrands(array $rows): void
    {
        foreach ($rows as $row) {
            Brand::create([
                'title' => trim((string) $row['title']),
                'image' => trim((string) ($row['image'] ?? '')) ?: null,
                'is_active' => true,
            ]);
        }
    }

    private function importProducts(array $rows): void
    {
        $categories = Category::pluck('id', 'name');
        $brands = Brand::pluck('id', 'title');
        $units = Unit::pluck('id', 'unit_code');
        $warehouses = DB::table('warehouses')->where('is_active', true)->pluck('id')->all();
        foreach ($rows as $row) {
            $code = trim((string) $row['code']);
            $unitId = $units[trim((string) $row['unit_code'])] ?? null;
            $categoryId = $categories[trim((string) $row['category'])] ?? null;
            if (!$unitId || !$categoryId) throw new RuntimeException("Catalog relationship resolution failed for product {$code}.");
            $variantNames = array_values(array_filter(array_map('trim', explode(',', (string) ($row['variant_name'] ?? ''))), 'strlen'));
            [$variantOptions, $variantValues] = ProductController::parseProductImportVariantValue((string) ($row['variant_value'] ?? ''));
            $cost = (float) str_replace(',', '', (string) ($row['cost'] ?? 0));
            $margin = (float) ($row['profit_margin'] ?? 0);
            $price = $cost > 0 && ($row['profit_margin'] ?? '') !== ''
                ? $cost * (1 + $margin / 100)
                : (float) str_replace(',', '', (string) ($row['price'] ?? 0));
            $product = Product::create([
                'name' => trim((string) $row['name']),
                'slug' => Str::slug((string) $row['name']),
                'code' => $code,
                'type' => strtolower(trim((string) ($row['type'] ?? 'standard'))),
                'barcode_symbology' => 'C128',
                'brand_id' => ($row['brand'] ?? '') !== '' ? ($brands[trim((string) $row['brand'])] ?? null) : null,
                'category_id' => $categoryId,
                'unit_id' => $unitId,
                'purchase_unit_id' => $unitId,
                'sale_unit_id' => $unitId,
                'cost' => $cost,
                'price' => $price,
                'profit_margin' => $margin,
                'profit_margin_type' => 'percentage',
                'wholesale_price' => (float) str_replace(',', '', (string) ($row['wholesale_price'] ?? 0)),
                'qty' => 0,
                'tax_method' => 1,
                'image' => is_file(public_path('images/product/zummXD2dvAtI.png')) ? 'zummXD2dvAtI.png' : null,
                'is_variant' => $variantNames !== [],
                'variant_option' => $variantOptions ? json_encode($variantOptions) : null,
                'variant_value' => $variantValues ? json_encode($variantValues) : null,
                'product_details' => (string) ($row['product_details'] ?? ''),
                'in_stock' => true,
                'is_active' => true,
            ]);
            if ($variantNames) {
                $itemCodes = array_map('trim', explode(',', (string) $row['item_code']));
                $costs = array_map('trim', explode(',', (string) $row['additional_cost']));
                $prices = array_map('trim', explode(',', (string) $row['additional_price']));
                if (count($variantNames) !== count($itemCodes) || count($variantNames) !== count($costs) || count($variantNames) !== count($prices)) {
                    throw new RuntimeException("Catalog variant lists do not align for product {$code}.");
                }
                foreach ($variantNames as $position => $name) {
                    $variant = Variant::firstOrCreate(['name' => $name]);
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'position' => $position + 1,
                        'item_code' => $itemCodes[$position],
                        'additional_cost' => (float) $costs[$position],
                        'additional_price' => (float) $prices[$position],
                        'qty' => 0,
                    ]);
                    foreach ($warehouses as $warehouseId) {
                        Product_Warehouse::create(['product_id' => $product->id, 'variant_id' => $variant->id, 'warehouse_id' => $warehouseId, 'qty' => 0]);
                    }
                }
            } else {
                foreach ($warehouses as $warehouseId) {
                    Product_Warehouse::create(['product_id' => $product->id, 'variant_id' => null, 'warehouse_id' => $warehouseId, 'qty' => 0]);
                }
            }
        }
    }

    private function processProductImages(array $rows): void
    {
        $items = [];
        foreach ($rows as $row) {
            foreach (array_filter(array_map('trim', explode(',', (string) ($row['image'] ?? '')))) as $source) {
                $key = hash('sha256', strtolower(trim((string) $row['code'])).'|'.$source);
                $items[] = ['key' => $key, 'product_code' => trim((string) $row['code']), 'source' => $source,
                    'filename_base' => 'import_'.substr($key, 0, 40), 'status' => 'pending'];
            }
        }
        $service = app(ProductImportImageService::class);
        $token = $service->createManifest($items);
        if (!$token) throw new RuntimeException('Authoritative catalog does not contain product image references.');
        $lastCompleted = -1;
        $stagnantBatches = 0;
        do {
            $result = $service->process($token, 20);
            $completed = (int) ($result['completed'] ?? 0);
            if ($completed > $lastCompleted) {
                $lastCompleted = $completed;
                $stagnantBatches = 0;
            } else {
                $stagnantBatches++;
            }
            if ($stagnantBatches >= 5) {
                throw new RuntimeException('Catalog image import failed: '.json_encode(array_values(array_filter($result['items'], fn (array $item) => ($item['status'] ?? null) === 'failed'))));
            }
            if (($result['failed'] ?? 0) > 0) usleep(250000);
        } while (($result['pending'] ?? 0) > 0 || ($result['failed'] ?? 0) > 0);
    }

    private function stableRecords(array $codes): array
    {
        $products = Product::query()->whereIn('products.code', $codes)->where('products.is_active', true)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->orderBy('products.code')
            ->select('products.id', 'products.code', 'products.name', 'products.type', 'products.is_variant', 'products.is_active',
                'categories.name as category', 'brands.title as brand', 'units.unit_code as unit')
            ->get();
        return $products->map(function ($product): array {
            $variants = DB::table('product_variants as pv')->join('variants as v', 'v.id', '=', 'pv.variant_id')
                ->where('pv.product_id', $product->id)->orderBy('pv.position')
                ->get(['pv.position', 'v.name', 'pv.item_code', 'pv.additional_cost', 'pv.additional_price'])
                ->map(fn ($variant) => [
                    'position' => (int) $variant->position,
                    'name' => (string) $variant->name,
                    'item_code' => (string) $variant->item_code,
                    'additional_cost' => (float) $variant->additional_cost,
                    'additional_price' => (float) $variant->additional_price,
                ])->all();
            return [
                'code' => (string) $product->code,
                'name' => (string) $product->name,
                'type' => (string) $product->type,
                'category' => (string) $product->category,
                'brand' => $product->brand !== null ? (string) $product->brand : null,
                'unit' => (string) $product->unit,
                'is_variant' => (bool) $product->is_variant,
                'is_active' => (bool) $product->is_active,
                'variants' => $variants,
            ];
        })->all();
    }

    private function fingerprint(array $records): string
    {
        return hash('sha256', json_encode($records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
    }

    private function imageEvidence(array $codes, bool $required): array
    {
        $references = [];
        $broken = [];
        foreach (Product::whereIn('code', $codes)->where('is_active', true)->orderBy('code')->get(['code', 'image']) as $product) {
            $images = array_values(array_filter(array_map('trim', explode(',', (string) $product->image)), fn (string $image) => $image !== '' && $image !== 'zummXD2dvAtI.png'));
            $references[$product->code] = $images;
            if ($required && $images === []) $broken[] = ['code' => $product->code, 'reason' => 'missing_reference'];
            foreach ($images as $image) {
                if (filter_var($image, FILTER_VALIDATE_URL) || !is_file(public_path('images/product/'.$image))) {
                    if ($required) $broken[] = ['code' => $product->code, 'image' => $image, 'reason' => 'missing_file'];
                }
            }
        }
        return [
            'required' => $required,
            'expected_products_with_images' => $required ? count($codes) : 0,
            'references_present' => count(array_filter($references)),
            'files_present' => array_sum(array_map(fn (array $images) => count(array_filter($images, fn (string $image) => is_file(public_path('images/product/'.$image)))), $references)),
            'broken_references' => $broken,
            'by_product' => $references,
        ];
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) throw new RuntimeException("Unable to open catalog source: {$path}");
        try {
            $header = fgetcsv($handle);
            if (!$header) throw new RuntimeException("Catalog source is empty: {$path}");
            $header = ProductController::normalizeProductImportHeaders($header);
            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if (count($values) !== count($header)) throw new RuntimeException("Catalog source column mismatch: {$path}");
                $rows[] = array_combine($header, $values);
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
