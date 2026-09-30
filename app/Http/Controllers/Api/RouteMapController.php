<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RouteMapController extends Controller
{
    use ProvidesThemeBackgrounds;

    public function index()
    {
        $routes = Route::getRoutes();
        $result = [];

        foreach ($routes as $route) {
            // Only include API GET routes
            if (!in_array('GET', $route->methods())) continue;
            if (!Str::startsWith($route->uri(), 'api/')) continue;

            $uri = $route->uri();
            $action = $route->getActionName();

            // Dynamic route with parameters
            if (preg_match_all('/\{(.*?)\}/', $uri, $matches)) {
                $params = $matches[1];
                $model = $this->guessModel($action);

                if ($model && class_exists($model)) {
                    // Load IDs/slugs
                    $table = (new $model)->getTable();

                    // Check if 'slug' column exists
                    $hasSlug = Schema::hasColumn($table, 'slug');

                    // Load appropriate columns
                    $items = $hasSlug
                        ? $model::all(['id', 'slug'])->map(fn($x) => $x->slug)
                        : $model::all(['id'])->map(fn($x) => $x->id);

                    foreach ($items as $value) {
                        $filled = $uri;

                        foreach ($params as $param) {
                            $filled = str_replace("{" . $param . "}", $value, $filled);
                        }

                        $result[] = "/" . $filled;
                    }
                } else {
                    // Model unknown → skip dynamic expansion
                    $result[] = "/" . $uri;
                }
            } else {
                // Static path
                $result[] = "/" . $uri;
            }
        }

        // Manually add routes that must include query parameters (not represented by Route URIs)
        // so the app can fetch full datasets for offline caching.
        $result[] = '/api/pos/data?offline=1';

        return response()->json([
            "routes" => array_values(array_unique($result)),
            "debug_bar" => env("APP_DEBUG", false) ? true : false,
        ]);
    }

    private function guessModel($action)
    {
        // Format: App\Http\Controllers\PostController@index
        if (!str_contains($action, '@')) return null;

        [$controller] = explode('@', $action);

        $name = class_basename($controller);     // PostController → Post
        $modelName = str_replace('Controller', '', $name);

        $possibleModel = "App\\Models\\$modelName";

        return class_exists($possibleModel) ? $possibleModel : null;
    }
}
