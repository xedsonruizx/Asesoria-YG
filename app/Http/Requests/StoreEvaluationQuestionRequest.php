<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Tipos de pregunta que tienen puntos individuales por opción
        $typesWithIndividualPoints = ['select', 'radio', 'checkbox'];
        $hasIndividualPoints = in_array($this->question_type, $typesWithIndividualPoints);
        
        return [
            'category_id' => 'required|exists:evaluation_categories,id',
            'question_text' => 'required|string|max:500',
            'question_type' => 'required|in:text,textarea,select,number,checkbox,yes_no,radio',
            'options' => 'nullable|array',
            'placeholder' => 'nullable|string|max:255',
            'min_value' => 'nullable|integer',
            'max_value' => 'nullable|integer|gte:min_value',
            'points' => $hasIndividualPoints ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('evaluation_questions', 'order')
                    ->where('category_id', $this->category_id)
                    ->whereNull('deleted_at')
            ],
            'show_condition' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'question_text.required' => 'El texto de la pregunta es obligatorio.',
            'question_text.max' => 'El texto de la pregunta no puede exceder 500 caracteres.',
            'question_type.required' => 'El tipo de pregunta es obligatorio.',
            'question_type.in' => 'El tipo de pregunta seleccionado no es válido.',
            'placeholder.max' => 'El placeholder no puede exceder 255 caracteres.',
            'min_value.integer' => 'El valor mínimo debe ser un número entero.',
            'max_value.integer' => 'El valor máximo debe ser un número entero.',
            'max_value.gte' => 'El valor máximo debe ser mayor o igual al valor mínimo.',
            'points.required' => 'Los puntos son obligatorios para este tipo de pregunta.',
            'points.integer' => 'Los puntos deben ser un número entero.',
            'points.min' => 'Los puntos deben ser mayor o igual a 0.',
            'order.required' => 'El orden es obligatorio.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser mayor a 0.',
            'order.unique' => 'Este orden ya está ocupado en la categoría seleccionada. Por favor, elija un número diferente.',
            'is_required.boolean' => 'El campo requerido debe ser verdadero o falso.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'question_text' => 'texto de la pregunta',
            'question_type' => 'tipo de pregunta',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'points' => 'puntos',
            'order' => 'orden',
            'is_required' => 'campo requerido',
            'is_active' => 'campo activo',
        ];
    }
}