<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasLevel
{
    public function handle(Request $request, Closure $next, string ...$levels): Response
    {
        $user = $request->user();
        $allowedLevels = array_map('intval', $levels);

        abort_unless($user !== null && in_array((int) $user->level, $allowedLevels, true), 403);

        return $next($request);
    }
}
