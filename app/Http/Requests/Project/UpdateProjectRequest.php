<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Services\ProjectAccessService;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && app(ProjectAccessService::class)->canManageProject($this->user(), $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = $this->baseRules();
        $rules['code'] = [
            'required',
            'string',
            'max:30',
            'alpha_dash',
            Rule::unique(Project::class)->ignore($this->route('project')),
        ];
        $rules['progress'] = ['required', 'integer', 'min:0', 'max:100'];

        return $rules;
    }
}
