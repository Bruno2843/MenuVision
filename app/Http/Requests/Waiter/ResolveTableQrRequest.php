<?php

namespace App\Http\Requests\Waiter;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class ResolveTableQrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The `role:mozo` middleware already refuses anyone else; repeating the role
     * here matches the rest of the waiter requests and keeps the authorization
     * readable at the request itself.
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
            'code' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'No se recibió el código QR.',
        ];
    }
}
