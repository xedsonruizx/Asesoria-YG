<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use App\Models\Post;

class StorePostRequest extends FormRequest
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
            // Excerpt ya no es requerido desde el form, se genera automáticamente
            'excerpt' => [
                'nullable',
                'string'
            ],
            'slug' => [
                'nullable',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:posts,slug'
            ],
            // Meta description ahora es requerido desde el formulario
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
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.min' => 'El título debe tener al menos 5 caracteres.',
            'title.max' => 'El título no puede exceder 255 caracteres.',
            'title.regex' => 'El título contiene caracteres no permitidos.',
            'content.required' => 'El contenido es obligatorio.',
            'content.min' => 'El contenido debe tener al menos 50 caracteres.',
            'content.max' => 'El contenido no puede exceder 100,000 caracteres.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'slug.unique' => 'Este slug ya está en uso.',
            'meta_description.required' => 'La meta descripción es obligatoria.',
            'meta_description.min' => 'La meta descripción debe tener al menos 50 caracteres.',
            'meta_description.max' => 'La meta descripción no puede exceder 160 caracteres.',
            'featured_image.file' => 'El archivo debe ser válido.',
            'featured_image.mimes' => 'El archivo debe ser de tipo: jpeg, png, jpg, webp, mp4, avi, mov, wmv, flv o webm.',
            'featured_image.max' => 'El archivo no puede ser mayor a 50MB.',
            'featured_image.dimensions' => 'La imagen debe tener entre 300x200 y 2000x2000 píxeles.',
            'file.mimes' => 'El archivo debe ser de tipo: pdf, doc, docx, txt, zip o rar.',
            'file.max' => 'El archivo no puede ser mayor a 20MB.',
            'tag_categories.required' => 'Debe seleccionar al menos un tag.',
            'tag_categories.min' => 'Debe seleccionar al menos un tag.',
            'tag_categories.max' => 'No puede seleccionar más de 10 tags.',
            'tag_categories.*.exists' => 'Uno o más tags seleccionados no son válidos.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Generar excerpt automáticamente desde el content
        if (!empty($this->content)) {
            // Limpiar HTML tags y obtener texto plano
            $plainText = strip_tags($this->content);
            // Generar excerpt de máximo 300 caracteres
            $excerpt = Str::limit($plainText, 300, '...');
            
            $this->merge([
                'excerpt' => $excerpt
            ]);
        }

        // Generar slug automáticamente si no se proporciona
        if (empty($this->slug) && !empty($this->title)) {
            $baseSlug = Str::slug($this->title);
            $slug = $baseSlug;
            $counter = 1;
            
            while (Post::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $this->merge([
                'slug' => $slug
            ]);
        }

        // Asegurar que is_premium sea boolean
        $this->merge([
            'is_premium' => $this->boolean('is_premium')
        ]);
    }
}