<?php

namespace App\Http\Requests\Waiter;

use App\Enums\Role;
use App\Support\TableQr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * The order form posts a quantity for every dish on the menu, using the
     * dish id as the array key and leaving the ones the waiter did not order
     * at zero. Normalise both into a list of `{dish_id, cantidad, observaciones}`
     * pairs so the rules below can address each field directly.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn (array $item): bool => (int) ($item['cantidad'] ?? 0) > 0)
            ->map(fn (array $item, int $dishId): array => [
                'dish_id' => $dishId,
                'cantidad' => (int) $item['cantidad'],
                'observaciones' => $this->observacion($item),
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
            // When the form was reached through the scanner it carries the QR
            // code instead of a chosen table, and the table is resolved from
            // that code rather than read from the request.
            'codigo_qr' => ['nullable', 'string', 'max:255'],
            'table_id' => ['required_without:codigo_qr', 'nullable', 'integer', 'exists:tables,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.observaciones' => ['nullable', 'string', 'max:500'],
            'items.*.dish_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('dishes', 'id')->where('disponible', true),
            ],
        ];
    }

    /**
     * Add the errors the rules above cannot express on their own.
     *
     * The table is resolved from the scanned code instead of being taken from
     * the request, so the only thing validation has to say about a code that
     * does not resolve is that it cannot produce an order.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $codigo = trim((string) $this->input('codigo_qr', ''));

            if ($codigo !== '' && TableQr::resolver($codigo) === null) {
                $validator->errors()->add(
                    'codigo_qr',
                    'No se encontró una mesa asociada a este código QR.'
                );
            }
        });
    }

    /**
     * The table this order belongs to.
     *
     * Resolved from the scanned QR code when the form carries one, so the table
     * cannot be swapped by editing the request; falls back to the table that was
     * posted when the form was opened from the "Tomar pedido" link instead.
     */
    public function tableId(): int
    {
        $codigo = trim((string) $this->input('codigo_qr', ''));

        if ($codigo !== '') {
            return TableQr::resolver($codigo)['table']->id;
        }

        return (int) $this->validated('table_id');
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'table_id.required_without' => 'El campo mesa es obligatorio.',
            'items.required' => 'Agregá al menos un plato al pedido.',
            'items.min' => 'Agregá al menos un plato al pedido.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.dish_id.exists' => 'Alguno de los platos ya no está disponible.',
            'items.*.dish_id.distinct' => 'El mismo plato no se puede repetir.',
            'items.*.observaciones.max' => 'La observación es demasiado larga.',
        ];
    }

    /**
     * The note the kitchen reads for a dish, or null when the waiter left it
     * blank: an empty input must not become an empty observation.
     */
    protected function observacion(array $item): ?string
    {
        $observacion = trim((string) ($item['observaciones'] ?? ''));

        return $observacion === '' ? null : $observacion;
    }
}
