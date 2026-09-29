<?php

namespace App\Http\Requests\Waiter;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Mozo;
    }

    /**
     * The waiter confirms a pending order, rejects it, and resolves one the
     * kitchen handed back. Anything else is rejected, so the allowed value is
     * derived from the bound order instead of being hardcoded here.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in($this->route('order')?->estado?->waiterTransitions() ?? []),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.in' => 'Este pedido ya no se puede modificar desde el mozo.',
        ];
    }
}
