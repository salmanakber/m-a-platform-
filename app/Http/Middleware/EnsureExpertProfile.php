<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureExpertProfile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isExpert()) {
            // Heal missing user_id links (e.g. import/login email match).
            $user->resolvePortalExpert();
        }

        return $next($request);
    }
}
