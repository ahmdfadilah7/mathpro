<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectAccessService;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly ProjectAccessService $accessService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Projects/Index', $this->projectService->getIndexData(
            $request->only(['search', 'status', 'priority', 'division_id', 'department_id', 'manager_id']),
            $request->user()
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'formOptions' => $this->projectService->getFormOptions(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projectService->create($request->validated(), $request->user());

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "Project \"{$project->name}\" berhasil dibuat.",
                'redirect' => route('projects.show', $project),
            ]);
    }

    public function show(Request $request, Project $project): Response
    {
        return Inertia::render(
            'Projects/Show',
            $this->projectService->getShowData($project, $request->user())
        );
    }

    public function edit(Request $request, Project $project): Response
    {
        $this->accessService->authorizeManageProject($request->user(), $project);

        $project->load([
            'division:id,name,code',
            'department:id,name,code,division_id',
            'manager:id,name',
        ]);

        return Inertia::render('Projects/Edit', [
            'project' => (new ProjectResource($project))->resolve(),
            'formOptions' => $this->projectService->getFormOptions(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projectService->update($project, $request->validated(), $request->user());

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => 'Project berhasil diperbarui.',
                'redirect' => route('projects.show', $project),
            ]);
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->accessService->authorizeManageProject($request->user(), $project);

        $name = $project->name;
        $this->projectService->delete($project, $request->user());

        return redirect()
            ->route('projects.index')
            ->with('swal', [
                'title' => 'Terhapus!',
                'message' => "Project \"{$name}\" berhasil dihapus.",
                'redirect' => route('projects.index'),
            ]);
    }
}
