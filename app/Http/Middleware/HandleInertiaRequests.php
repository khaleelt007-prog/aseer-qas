<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\PermissionService;
use App\Services\DataAccessService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    protected $permissionService;
    protected $dataAccessService;

    public function __construct(PermissionService $permissionService, DataAccessService $dataAccessService)
    {
        $this->permissionService = $permissionService;
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = [];
        $dataAccess = [];

        if ($user) {
            // Get permissions from session (cached during login)
            $permissions = $this->permissionService->getPermissionsFromSession();

            // Get data access from session (cached during login)
            $dataAccess = $this->dataAccessService->getDataAccessFromSession();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
                'dataAccess' => $dataAccess,
            ],
            'app_version' => config('app_version.version'),
            'locale' => app()->getLocale(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'translations' => [
                'navigation' => __('navigation'),
                'quality' => __('quality'),
            ],
        ];
    }
}
