<?php

namespace App\Http\Requests\Admin;

use App\Support\SystemRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('users.update') && $user->can('roles.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assignable = SystemRole::assignableRoleNamesFor($this->user());

        return [
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::in($assignable)],
        ];
    }

    /**
     * Get the custom messages for the validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.present' => 'The roles payload must always be present, even when no roles are selected.',
            'roles.array' => 'The roles payload must be sent as an array of role names.',
            'roles.*.distinct' => 'Each role may only be selected once.',
            'roles.*.exists' => 'One of the selected roles does not exist in the system.',
        ];
    }
}
