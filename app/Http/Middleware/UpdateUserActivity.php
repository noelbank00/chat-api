<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = auth()->user()) {
            $user->update([
                'is_active' => true,
                'last_activity_at' => now()
            ]);
        }

        return $next($request);
    }
}
