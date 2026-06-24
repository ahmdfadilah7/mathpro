<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? $request->user()->load(['role:id,name,slug,permissions', 'department:id,name,division_id', 'department.division:id,name'])
                    : null,
                'abilities' => $request->user()
                    ? app(PermissionService::class)->abilitiesFor($request->user())
                    : null,
            ],
            'app' => [
                'name' => config('app.name', 'MathPro'),
            ],
            'navbar' => function () use ($request) {
                if (! $request->user()) {
                    return ['notifications' => [], 'notificationCount' => 0];
                }

                $notifications = app(NotificationService::class)->forUser($request->user());

                return [
                    'notifications' => $notifications,
                    'notificationCount' => count($notifications),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'swal' => fn () => $request->session()->get('swal'),
            ],
        ];
    }
}
