<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\pN\s\-_.,!?()]+$/u',
                Rule::unique('evaluation_categories', 'name')
            ],
            'slug' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('evaluation_categories', 'slug')
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'is_active' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'name.regex' => 'El nombre contiene caracteres no válidos.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'slug.min' => 'El slug debe tener al menos 2 caracteres.',
            'slug.max' => 'El slug no puede exceder 255 caracteres.',
            'slug.regex' => 'El slug debe contener solo letras minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe una categoría con este slug.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FF5733).'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'icono',
            'max_score' => 'puntuación máxima',
            'order' => 'orden',
            'is_active' => 'estado activo'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar espacios en blanco del nombre
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name)
            ]);
        }

        // Convertir is_active a boolean
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}