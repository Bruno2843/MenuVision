<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateTableRequest extends StoreTableRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The unique check ignores the table being renamed, so a table can keep its
     * number when only its description of the room changes.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' => ['required', 'string', 'max:50', Rule::unique('tables', 'numero')->ignore($this->route('table'))],
        ];
    }
}
