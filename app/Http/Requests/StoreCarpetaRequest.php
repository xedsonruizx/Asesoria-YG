<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Carpeta;

class StoreCarpetaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\pN\s\-_.,!?()]+$/u',
                Rule::unique('carpetas', 'nombre')->where(function ($query) {
                    return $query->where('padre_id', $this->padre_id)
                                 ->whereNull('deleted_at');
                })
            ],
            'slug' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('carpetas', 'slug')->whereNull('deleted_at')
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'icono' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-_]+$/'
            ],
            'orden' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999'
            ],
            'activa' => [
                'boolean'
            ],
            'padre_id' => [
                'nullable',
                'exists:carpetas,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Verificar que la carpeta padre esté activa
                        $padre = Carpeta::find($value);
                        if (!$padre || !$padre->activa) {
                            $fail('La carpeta padre debe estar activa.');
                        }
                        
                        // Verificar límite de profundidad (máximo 5 niveles)
                        if ($padre && $padre->nivel >= 4) {
                            $fail('No se pueden crear más de 5 niveles de carpetas.');
                        }
                    }
                }
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la carpeta es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
            'nombre.regex' => 'El nombre contiene caracteres no válidos.',
            'nombre.unique' => 'Ya existe una carpeta con este nombre en la misma ubicación.',
            'slug.min' => 'El slug debe tener al menos 2 caracteres.',
            'slug.max' => 'El slug no puede exceder 255 caracteres.',
            'slug.regex' => 'El slug debe contener solo letras minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe una carpeta con este slug.',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FF5733).',
            'icono.max' => 'El icono no puede exceder 50 caracteres.',
            'icono.regex' => 'El icono contiene caracteres no válidos.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser mayor o igual a 0.',
            'orden.max' => 'El orden no puede ser mayor a 9999.',
            'padre_id.exists' => 'La carpeta padre seleccionada no existe.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'slug' => 'slug',
            'descripcion' => 'descripción',
            'color' => 'color',
            'icono' => 'icono',
            'orden' => 'orden',
            'activa' => 'estado activo',
            'padre_id' => 'carpeta padre'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar espacios en blanco del nombre
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }

        // Convertir activa a boolean
        if ($this->has('activa')) {
            $this->merge([
                'activa' => filter_var($this->activa, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Si no se proporciona orden, asignar el siguiente disponible
        if (!$this->has('orden') || $this->orden === null) {
            $maxOrden = Carpeta::where('padre_id', $this->padre_id)
                              ->whereNull('deleted_at')
                              ->max('orden') ?? 0;
            $this->merge([
                'orden' => $maxOrden + 1
            ]);
        }

        // Asegurar que padre_id sea null si está vacío
        if ($this->has('padre_id') && empty($this->padre_id)) {
            $this->merge([
                'padre_id' => null
            ]);
        }
    }
}