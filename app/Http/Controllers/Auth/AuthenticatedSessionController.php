<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): BaseResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if ($user) {
            $this->activityLogService->log(
                $user,
                'login',
                $user,
                "{$user->name} masuk ke sistem",
                [],
                $request
            );
        }

        return Inertia::location($request->session()->pull('url.intended', route('dashboard')));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): BaseResponse
    {
        $user = $request->user();
        if ($user) {
            $this->activityLogService->log(
                $user,
                'logout',
                $user,
                "{$user->name} keluar dari sistem",
                [],
                $request
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return Inertia::location(route('login'));
    }
}
