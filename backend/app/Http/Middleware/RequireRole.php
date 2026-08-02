<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    /**
     * Handle an incoming request.
     * Expect role(s) as middleware parameter (comma or pipe separated).
     */
    public function handle(Request $request, Closure $next, string $roles = '')
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (empty($roles)) {
            return $next($request);
        }

        $expected = preg_split('/[,|]/', $roles, -1, PREG_SPLIT_NO_EMPTY);

        // Use spatie's hasAnyRole method (User model uses HasRoles)
        if (method_exists($user, 'hasAnyRole')) {
            if (! $user->hasAnyRole($expected)) {
                return response()->json(['message' => 'Forbidden. Insufficient role.'], 403);
            }
            return $next($request);
        }

        // Fallback: no roles support
        return response()->json(['message' => 'Forbidden. Roles not configured.'], 403);
    }
}
