<?php

namespace App\Http\Requests\Waiter;

use App\Enums\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOrderRequest extends FormRequest
{
    /**
     * The form posts a quantity for every dish it offers, using the dish id as
     * the array key and leaving the ones the waiter removed at zero. Turning
     * that shape into a list of `{dish_id, cantidad}` pairs lets the rules
     * below address each field directly, and lets the controller tell the
     * dishes that stay from the ones being added by looking at what the order
     * already holds.
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
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'preparar_igual' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.dish_id' => [
                'required',
                'integer',
                'distinct',
                // A dish the order already holds stays sendable even if the
                // kitchen marked it as sold out in the meantime, so the waiter
                // is not forced to drop it without noticing.
                Rule::exists('dishes', 'id')->where(
                    fn ($query) => $query
                        ->where('disponible', true)
                        ->orWhereIn('id', $this->dishIdsOnTheOrder())
                ),
            ],
        ];
    }

    /**
     * The order has to be a handback for the payload to be meaningful, and
     * that must hold even when the payload is malformed: otherwise a stale
     * form could push an order the kitchen already has back into the kitchen.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->route('order')?->puedeReenviarse()) {
                $validator->errors()->add('estado', 'Este pedido ya no se puede reenviar a cocina.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'El pedido no puede quedar sin platos.',
            'items.min' => 'El pedido no puede quedar sin platos.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.dish_id.exists' => 'Alguno de los platos ya no está disponible.',
            'items.*.dish_id.distinct' => 'El mismo plato no se puede repetir.',
        ];
    }

    /**
     * The dish ids the order is made of right now.
     *
     * @return array<int, int>
     */
    private function dishIdsOnTheOrder(): array
    {
        return $this->route('order')?->items->pluck('dish_id')->all() ?? [];
    }
}
