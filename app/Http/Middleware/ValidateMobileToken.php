<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\GeneralSetting;
use App\Models\MobileToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ValidateMobileToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');

        if (strpos(env('APP_URL'), 'salepropos.com/demo') != false && strpos(env('APP_URL'), 'salepropos.com/demo') >= 0) {
            return $next($request);
        }

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token missing',
                'invalid_license_token' => true,
            ], 401);
        }

        $activeTokens = MobileToken::where('is_active', true)->get();

        $matched = false;

        foreach ($activeTokens as $storedToken) {
            if (Hash::check($token, $storedToken->token)) {
                $matched = true;

                // Optionally update last_active timestamp
                $storedToken->update([
                    'name' => Auth::user()->name ?? 'Device ' . $request->ip(),
                    'last_active' => now(),
                    'ip' => $request->ip(),
                ]);
                break;
            }
        }

        if (!$matched) {
            return response()->json(['success' => false, 'message' => 'Your device isn\'t in our records. Please clear data of this app.', 'invalid_license_token' => true,], 401);
        }

        return $next($request);
    }
}
