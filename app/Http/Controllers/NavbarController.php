<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NavbarController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $globalSearchService,
        private readonly NotificationService $notificationService
    ) {}

    public function search(Request $request): JsonResponse
    {
        return response()->json(
            $this->globalSearchService->search(
                $request->user(),
                $request->query('q')
            )
        );
    }

    public function dismissNotification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_key' => ['required', 'string', 'max:128'],
        ]);

        $this->notificationService->dismiss(
            $request->user(),
            $validated['notification_key']
        );

        return back();
    }

    public function dismissAllNotifications(Request $request): RedirectResponse
    {
        $this->notificationService->dismissAll($request->user());

        return back();
    }
}
