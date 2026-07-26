<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\UserPermissionsEnum;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !UserPermissionsEnum::from($user->role)->isAdminRole()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin access required.'], 403);
            }
            abort(403, 'Admin access required.');
        }
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login.show')->withErrors(['email' => 'Your account has been deactivated.']);
        }
        return $next($request);
    }
}
