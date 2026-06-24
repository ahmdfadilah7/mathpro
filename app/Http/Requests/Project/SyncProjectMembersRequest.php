<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectMemberAccess;
use App\Models\Project;
use App\Services\ProjectAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProjectMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && app(ProjectAccessService::class)->canManageMembers($this->user(), $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'members' => ['present', 'array'],
            'members.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'members.*.access' => ['required', Rule::enum(ProjectMemberAccess::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'members' => 'anggota tim',
            'members.*.user_id' => 'user',
            'members.*.access' => 'akses',
        ];
    }
}
