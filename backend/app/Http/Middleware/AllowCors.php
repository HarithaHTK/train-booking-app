<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        $origin = $request->header('Origin');
        $allowedOrigins = array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5173'))));

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
