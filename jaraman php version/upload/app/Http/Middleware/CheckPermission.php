<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login.show');
        }
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) return $next($request);
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
        }
        abort(403, 'You do not have permission to perform this action.');
    }
}
