<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->can('update', $this->route('post')) ||
            auth()->id() === $this->route('post')->author_id
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $postId = $this->route('post')->id;
        
        return [
            'title' => [
                'required',
                'string',
                'min:5',
                'max:255',
                'regex:/^[\pL\pN\s\-_.,!?()]+$/u'
            ],
            'content' => [
                'required',
                'string',
                'min:50',
                'max:100000'
            ],
            'excerpt' => [
                'nullable',
                'string'
            ],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($postId)
            ],
            'meta_description' => [
                'required',
                'string',
                'min:50',
                'max:160'
            ],
            'status' => [
                'required',
                'in:draft,published'
            ],
            'is_premium' => [
                'boolean'
            ],
            'featured_image' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,webp,mp4,avi,mov,wmv,flv,webm',
                'max:51200', // 50MB para videos
            ],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,txt,zip,rar',
                'max:20480'
            ],
            'tag_categories' => [
                'required',
                'array',
                'min:1',
                'max:10'
            ],
            'tag_categories.*' => [
                'integer',
                'exists:tags_category,id'
            ],
            'published_at' => [
                'nullable',
                'date',
                'before_or_equal:now'
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio para actualizar.',
            'title.min' => 'El título debe tener al menos 5 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'content.min' => 'El contenido debe tener al menos 50 caracteres.',
            'meta_description.required' => 'La meta descripción es obligatoria.',
            'meta_description.min' => 'La meta descripción debe tener al menos 50 caracteres.',
            'meta_description.max' => 'La meta descripción no puede exceder 160 caracteres.',
            'tag_categories.required' => 'Debe seleccionar al menos un tag.',
            'published_at.before_or_equal' => 'La fecha de publicación no puede ser futura.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation()
    {
        // Generar slug automáticamente si no se proporciona
        if (!$this->slug && $this->title) {
            $this->merge([
                'slug' => Str::slug($this->title)
            ]);
        }
        
        // Generar excerpt automáticamente desde content (SIEMPRE)
        if ($this->content) {
            $this->merge([
                'excerpt' => Str::limit(strip_tags($this->content), 200)
            ]);
        } else {
            // Si no hay content, usar excerpt vacío
            $this->merge([
                'excerpt' => ''
            ]);
        }
        
        // Convertir is_premium a booleano
        $this->merge([
            'is_premium' => filter_var($this->is_premium, FILTER_VALIDATE_BOOLEAN)
        ]);
        
        // Debug log
        Log::info('=== PREPARACIÓN VALIDACIÓN ===', [
            'datos_originales' => $this->all(),
            'excerpt_generado' => $this->excerpt,
            'archivos' => [
                'featured_image' => $this->hasFile('featured_image'),
                'file' => $this->hasFile('file')
            ]
        ]);
    }
}