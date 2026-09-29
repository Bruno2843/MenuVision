<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'nombre')->ignore($category),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
