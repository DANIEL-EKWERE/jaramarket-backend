<?php

namespace App\Http\Middleware;

use App\Enums\UserPermissionsEnum;
use Closure;
use Illuminate\Http\Request;

class IsVendor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, [UserPermissionsEnum::VENDOR(), UserPermissionsEnum::ADMIN()])) {
            abort(403, 'Unauthorized action');
        }

        $request->merge(['is_vendor' => true]);

        return $next($request);
    }
}
