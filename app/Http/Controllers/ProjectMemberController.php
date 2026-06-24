<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\SyncProjectMembersRequest;
use App\Models\Project;
use App\Services\ProjectAccessService;
use App\Services\ProjectMemberService;
use Illuminate\Http\RedirectResponse;

class ProjectMemberController extends Controller
{
    public function __construct(
        private readonly ProjectMemberService $memberService,
        private readonly ProjectAccessService $accessService
    ) {}

    public function sync(SyncProjectMembersRequest $request, Project $project): RedirectResponse
    {
        $this->accessService->authorizeManageMembers($request->user(), $project);

        $this->memberService->sync(
            $project,
            $request->validated('members', []),
            $request->user()
        );

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => 'Anggota tim dan akses project berhasil disimpan.',
                'redirect' => route('projects.show', $project).'#team',
            ]);
    }
}
