<?php

namespace App\Http\Requests\Waiter;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * The order form posts a quantity for every dish on the menu, using the
     * dish id as the array key and leaving the ones the waiter did not order
     * at zero. Normalise both into a list of `{dish_id, cantidad}` pairs so
     * the rules below can address each field directly.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn (array $item): bool => (int) ($item['cantidad'] ?? 0) > 0)
            ->map(fn (array $item, int $dishId): array => [
                'dish_id' => $dishId,
                'cantidad' => (int) $item['cantidad'],
            ])
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Mozo;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'table_id' => ['required', 'integer', 'exists:tables,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.dish_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('dishes', 'id')->where('disponible', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Agregá al menos un plato al pedido.',
            'items.min' => 'Agregá al menos un plato al pedido.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.dish_id.exists' => 'Alguno de los platos ya no está disponible.',
            'items.*.dish_id.distinct' => 'El mismo plato no se puede repetir.',
        ];
    }
}
