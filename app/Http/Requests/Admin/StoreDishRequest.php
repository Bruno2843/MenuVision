<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreDishRequest extends FormRequest
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
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'imagen' => [
                'nullable',
                'file',
                'mimes:avif,webp,jpg,jpeg,png',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:4096',
            ],
            'quitar_imagen' => ['nullable', 'boolean'],
            'ingredientes' => ['nullable', 'array'],
            'ingredientes.*' => ['integer', 'exists:ingredients,id'],
            'modelo' => ['nullable', 'file', 'extensions:glb', 'max:102400', $this->modeloEsGlbValido()],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'disponible' => ['required', 'boolean'],
            'orden' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Custom messages for the rules whose default wording is either a raw limit
     * or says nothing about which formats are actually accepted.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imagen.max' => 'La foto no puede pesar más de 4 MB. Bajala de resolución y volvé a subirla.',
            'imagen.mimes' => 'La foto tiene que ser un JPG, PNG, WebP o AVIF.',
            'imagen.mimetypes' => 'La foto tiene que ser un JPG, PNG, WebP o AVIF.',
            // The raw limit would read "may not be greater than 102400", which
            // says nothing about what to do about it.
            'modelo.max' => 'El modelo no puede pesar más de 100 MB. Bajá la resolución de las texturas y volvé a exportarlo.',
        ];
    }

    /**
     * The four bytes every `.glb` file opens with.
     *
     * A `.glb` is a binary glTF container whose first four bytes are the ASCII
     * magic `glTF`. The `extensions` rule only looks at the name the browser
     * sent, which is under the control of whoever uploads the file; reading the
     * bytes is what makes the check real. Without it a renamed file would be
     * stored happily and only fail later, in the browser, on the dish page.
     */
    protected function modeloEsGlbValido(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $value instanceof UploadedFile) {
                return;
            }

            $cabecera = file_get_contents($value->getRealPath(), length: 4);

            if ($cabecera !== 'glTF') {
                $fail('El archivo no es un modelo GLB válido.');
            }
        };
    }
}
