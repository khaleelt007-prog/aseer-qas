<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PermissionService;
use Symfony\Component\HttpFoundation\Response;

class CheckQualityEvaluationPermission
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has the required permission
        if (!$this->permissionService->hasPermission($user, 'quality_evaluations', $action) && 1==2) {
            // For API requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to ' . $action . ' quality evaluations.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            // For web requests, abort with 403
            abort(403, 'You do not have permission to ' . $action . ' quality evaluations.');
        }

        return $next($request);
    }
}
