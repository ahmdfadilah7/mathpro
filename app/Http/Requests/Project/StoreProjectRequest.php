<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'progress' => $this->input('progress', 0) ?: 0,
            'manager_id' => $this->filled('manager_id') ? $this->manager_id : null,
            'budget' => $this->filled('budget') ? $this->budget : null,
            'description' => $this->filled('description') ? $this->description : null,
            'start_date' => $this->filled('start_date') ? $this->start_date : null,
            'due_date' => $this->filled('due_date') ? $this->due_date : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama project',
            'code' => 'kode project',
            'division_id' => 'divisi',
            'department_id' => 'departemen',
            'manager_id' => 'project manager',
            'start_date' => 'tanggal mulai',
            'due_date' => 'tanggal selesai',
        ];
    }

    /** @return array<string, mixed> */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique(Project::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'division_id' => ['required', 'exists:divisions,id'],
            'department_id' => [
                'required',
                'exists:departments,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $belongs = Department::where('id', $value)
                        ->where('division_id', $this->input('division_id'))
                        ->exists();
                    if (! $belongs) {
                        $fail('Departemen tidak termasuk dalam divisi yang dipilih.');
                    }
                },
            ],
            'manager_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
