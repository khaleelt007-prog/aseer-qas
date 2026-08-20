<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Only users whose group_id === 1 are considered Super Admins.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ((int) $user->group_id !== 1) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Super admin access required.',
                ], 403);
            }

            abort(403, 'Super admin access required.');
        }

        return $next($request);
    }
}
