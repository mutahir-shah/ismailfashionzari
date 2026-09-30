<?php

namespace App\Services\Stability;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SurfaceDiscoveryAudit
{
    /**
     * Known non-DataTable or utility POST/GET endpoints that should be excluded from DataTable discovery enforcement.
     *
     * @var array<string, string>
     */
    private array $discoveryAllowList = [
        'login' => 'Auth endpoint',
        'logout' => 'Auth endpoint',
        'password/email' => 'Auth endpoint',
        'password/reset' => 'Auth endpoint',
        'change-password' => 'User settings',
        'switch-theme' => 'Theme preference',
        'set-language/{locale}' => 'Locale switch',
        'clear-cache' => 'Admin utility',
        'report/monthly_sale/{year}' => 'Chart AJAX endpoint',
        'report/daily_sale/{year}/{month}' => 'Chart AJAX endpoint',
        'report/monthly_purchase/{year}' => 'Chart AJAX endpoint',
        'report/daily_purchase/{year}/{month}' => 'Chart AJAX endpoint',
        'products/variant-data/{id}' => 'Product variant modal subresource AJAX',
        'sales/product_sale/{id}' => 'Sale items modal subresource AJAX',
        'sales/offline_products/{warehouse_id}' => 'POS offline sync endpoint',
        'delivery/product_delivery/{id}' => 'Delivery items modal subresource AJAX',
        'quotations/product_quotation/{id}' => 'Quotation items modal subresource AJAX',
        'purchases/product_purchase/{id}' => 'Purchase items modal subresource AJAX',
        'transfers/product_transfer/{id}' => 'Transfer items modal subresource AJAX',
        'return-sale/product_return/{id}' => 'Return items modal subresource AJAX',
        'return-purchase/product_return/{id}' => 'Purchase return items modal subresource AJAX',
        'manufacturing/productions/product_production/{id}' => 'Modal subresource AJAX',
        'manufacturing/recipes/get-ingredient-data' => 'Recipe builder subresource AJAX',
        'manufacturing/products/getdata/{id}/{variant_id}' => 'Product variant lookup AJAX',
        'repair/service/{id}/parts-data' => 'Repair parts subresource AJAX',
        'payroll/monthly-data' => 'HRM monthly payroll modal helper AJAX',
        'restaurant/kitchen/dashboard/data' => 'KDS dynamic dashboard partial AJAX',
    ];

    /**
     * Perform the coverage audit.
     *
     * @param array<string, array<string, mixed>> $registeredSurfaces
     * @return array{
     *     passed: bool,
     *     discovered_count: int,
     *     registered_count: int,
     *     unregistered_count: int,
     *     unregistered_surfaces: array<string>,
     *     discovered_datatables: array<string>
     * }
     */
    public function audit(array $registeredSurfaces): array
    {
        $registeredUris = [];
        foreach ($registeredSurfaces as $surface) {
            $registeredUris[trim($surface['uri'], '/')] = $surface;
        }

        $discoveredDataTables = $this->discoverDataTablesFromRoutes();
        $discoveredFromViews = $this->discoverDataTablesFromViews();

        $allDiscoveredDataTables = array_values(array_unique(array_merge($discoveredDataTables, $discoveredFromViews)));
        $unregistered = [];

        foreach ($allDiscoveredDataTables as $uri) {
            $cleanUri = trim($uri, '/');
            if (isset($this->discoveryAllowList[$cleanUri])) {
                continue;
            }

            // Exclude external API endpoints and modal parameterized subresources
            if (str_starts_with($cleanUri, 'api/') || str_contains($cleanUri, '{')) {
                continue;
            }

            // Normalize aliases
            if ($cleanUri === 'customer/customer-data') {
                $cleanUri = 'customers/customer-data';
            }

            if (!isset($registeredUris[$cleanUri])) {
                $unregistered[] = $cleanUri;
            }
        }

        return [
            'passed' => count($unregistered) === 0,
            'discovered_count' => count($allDiscoveredDataTables),
            'registered_count' => count($registeredSurfaces),
            'unregistered_count' => count($unregistered),
            'unregistered_surfaces' => $unregistered,
            'discovered_datatables' => $allDiscoveredDataTables,
        ];
    }

    /**
     * Discover DataTable routes from application router.
     *
     * @return array<string>
     */
    private function discoverDataTablesFromRoutes(): array
    {
        $discovered = [];
        try {
            $routes = Route::getRoutes();

            foreach ($routes as $route) {
                $uri = $route->uri();
                $action = is_string($route->getActionName()) ? $route->getActionName() : '';

                if (
                    str_contains($uri, '-data') ||
                    str_contains($uri, '_data') ||
                    str_contains($uri, '/data') ||
                    str_contains(strtolower($action), 'data')
                ) {
                    // Exclude framework / debug routes
                    if (
                        !str_starts_with($uri, '_ignition') &&
                        !str_starts_with($uri, 'sanctum') &&
                        !str_starts_with($uri, 'telescope')
                    ) {
                        $discovered[] = $uri;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        return array_unique($discovered);
    }

    /**
     * Scan Blade templates for serverSide DataTable declarations.
     *
     * @return array<string>
     */
    private function discoverDataTablesFromViews(): array
    {
        $discovered = [];
        $searchDirs = [
            resource_path('views/backend'),
            resource_path('views/accounting'),
        ];

        foreach ($searchDirs as $viewsPath) {
            if (!File::isDirectory($viewsPath)) {
                continue;
            }

            $files = File::allFiles($viewsPath);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = $file->getContents();
                if (str_contains($content, 'serverSide: true') || str_contains($content, 'serverSide:true')) {
                    if (preg_match_all("/url\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
                        foreach ($matches[1] as $match) {
                            if (str_contains($match, 'data') || str_contains($match, 'report')) {
                                $discovered[] = trim($match, '/');
                            }
                        }
                    }
                }
            }
        }

        return array_unique($discovered);
    }
}
