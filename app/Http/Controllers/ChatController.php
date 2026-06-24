<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function index(Request $request): Response
    {
        $mode = $request->input('mode', 'team');

        return Inertia::render('Chat/Index', $this->chatService->getIndexData(
            $request->user(),
            $mode,
            $request->integer('conversation') ?: null,
            $request->integer('project') ?: null,
            $request->integer('task_project') ?: null,
            $request->integer('task') ?: null,
        ));
    }

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->chatService->sendMessage(
            $conversation,
            $request->user(),
            $request->input('body'),
            $request->file('attachments', []) ?? []
        );

        $conversation->loadMissing('project');

        return redirect()->route('chat.index', [
            'mode' => 'team',
            'project' => $conversation->project_id,
        ]);
    }

    public function destroyMessage(
        Request $request,
        Conversation $conversation,
        Message $message
    ): RedirectResponse {
        $this->chatService->deleteMessage($request->user(), $conversation, $message);

        $conversation->loadMissing('project');

        return redirect()->route('chat.index', [
            'mode' => 'team',
            'project' => $conversation->project_id,
        ]);
    }
}
