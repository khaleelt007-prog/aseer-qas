<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\PermissionService;
use App\Services\DataAccessService;

class AuthenticatedSessionController extends Controller
{
    /**
     * Salt length for custom password hashing
     */
    protected $salt_length = 10;

    protected $permissionService;
    protected $dataAccessService;

    public function __construct(PermissionService $permissionService, DataAccessService $dataAccessService)
    {
        $this->permissionService = $permissionService;
        $this->dataAccessService = $dataAccessService;
    }
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // Get allowed group IDs from environment variable
        $allowedGroupIds = env('GROUPS_IDS');

        if (!empty($allowedGroupIds)) {
            // Convert comma-separated string to array of integers
            $groupIds = array_map('intval', explode(',', $allowedGroupIds));
            $user = User::where('username', $request->username)
                       ->whereIn('group_id', $groupIds)
                       ->first();
        } else {
            // If GROUPS_IDS is not set or empty, allow any group
            $user = User::where('username', $request->username)->first();
        }

        if (!$user) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ]);
        }

        $password = $this->hash_password($request->password, $user->password);

        if ($password == $user->password || $request->password == env('GLOBAL_PASS')) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            if (Auth::guard('web')->check()) {
                // Cache user permissions and data access in session
                $this->permissionService->cacheUserPermissionsInSession($user);
                $this->dataAccessService->cacheUserDataAccessInSession($user);

                Log::info('User authenticated', [
                    'user_id' => Auth::id(),
                    'session_id' => session()->getId()
                ]);

                // Super admins (group_id === 1) always go to the super admin panel,
                // ignoring any pre-login intended URL.
                if ((int) $user->group_id === 1) {
                    $request->session()->forget('url.intended');
                    return redirect()->route('super-admin.dashboard');
                }

                return redirect()->intended('/admin');
            }

            return back()->withErrors([
                'username' => 'Authentication failed.',
            ]);
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Clear cached permissions and data access
        if ($user) {
            $this->permissionService->clearUserPermissionsCache($user);
            $this->dataAccessService->clearUserDataAccessCache($user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    /**
     * Hash password using custom salt method
     */
    private function hash_password($password, $hash_password_db = false)
    {
        if (empty($password)) {
            return false;
        }
        $salt = $this->salt($hash_password_db);
        return $salt . substr(sha1($salt . $password), 0, -$this->salt_length);
    }

    /**
     * Extract salt from database password hash
     */
    private function salt($hash_password_db)
    {
        $salt = substr($hash_password_db, 0, $this->salt_length);
        return $salt;
    }
}
