<?php

namespace App\Http\Requests\Kitchen;

use App\Enums\OrderStatus;
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
        return $this->user()?->role === Role::Cocina;
    }

    /**
     * The kitchen may only move an order to a status the enum allows from
     * where it currently sits, which resolves to a single next step plus the
     * "sin insumos" handback while the food is still being produced. An order
     * outside the kitchen's part of the flow yields an empty list and rejects
     * any submission.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in($this->route('order')?->estado?->kitchenTransitions() ?? []),
            ],
            'motivo_devolucion' => [
                'nullable',
                'string',
                'max:255',
                'required_if:estado,'.OrderStatus::SinInsumos->value,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.in' => 'Este pedido no se puede avanzar desde cocina.',
            'motivo_devolucion.required_if' => 'Contále al mozo qué insumo falta.',
        ];
    }
}
