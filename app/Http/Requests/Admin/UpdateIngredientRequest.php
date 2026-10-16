<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateIngredientRequest extends StoreIngredientRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The unique check has to ignore the row being edited, otherwise saving an
     * ingredient without renaming it would report a clash with itself.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'nombre')->ignore($this->route('ingredient')),
            ],
        ];
    }
}
