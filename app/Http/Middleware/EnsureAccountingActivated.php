<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AccountingConfig;

class EnsureAccountingActivated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = AccountingConfig::firstOrCreate(['id' => 1]);
        
        if (!$config->enabled) {
            return redirect()->route('accounting.activation.index');
        }
        
        return $next($request);
    }
}
