<?php

namespace App\Http\Requests\Admin;

use App\Support\SystemRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->can('users.create')) {
            return false;
        }

        $roles = $this->input('roles', []);
        if (is_array($roles) && $roles !== [] && ! $user->can('roles.update')) {
            return false;
        }

        return true;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::in($assignable)],
        ];
    }
}
