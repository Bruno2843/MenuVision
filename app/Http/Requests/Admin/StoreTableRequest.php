<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Administrador;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The status is deliberately not editable: whether a table is free or busy
     * is decided by the orders on it, and letting a number here would put the
     * panel at odds with the waiter panel, which releases a table on its own.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' => ['required', 'string', 'max:50', Rule::unique('tables', 'numero')],
        ];
    }
}
