<?php

namespace App\Http\Requests\User;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->slug === 'super-admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'department_id' => $this->filled('department_id') ? $this->department_id : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => [
                'nullable',
                'exists:departments,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value || ! $this->filled('division_id')) {
                        return;
                    }
                    $belongs = Department::where('id', $value)
                        ->where('division_id', $this->input('division_id'))
                        ->exists();
                    if (! $belongs) {
                        $fail('Departemen tidak sesuai dengan divisi.');
                    }
                },
            ],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'position' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'password',
            'role_id' => 'role',
            'department_id' => 'departemen',
        ];
    }
}
