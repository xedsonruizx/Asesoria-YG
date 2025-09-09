<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\TagCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     * Vista de clientes publicaciones
     */
    public function AdminIndex(Request $request)
    {
        $query = Post::with('tags');

        // Filtrar por tags si se proporciona
        if ($request->filled('tag_id')) {
            $query->withTags([$request->tag_id]);
        }

        // Filtrar por estado si se proporciona
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Búsqueda por título
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(10);
        $tags = TagCategory::active()->orderBy('name')->get();

        return Inertia::render('administration/Post', [
            'posts' => $posts,
            'tags' => $tags,
            'filters' => $request->only(['tag_id', 'status', 'search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tags = TagCategory::active()->orderBy('name')->get();
        
        return Inertia::render('administration/Posts/Create', [
            'availableTags' => $tags
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();
        Log::info("store");
        // Crear un slug único para la carpeta basado en el título
        $postSlug = Str::slug($validated['title']) . '-' . time();
        
        // Manejar subida de imagen
        if ($request->hasFile('featured_image')) {
            $imageFile = $request->file('featured_image');
            $imageExtension = $imageFile->getClientOriginalExtension();
            $imageName = 'imagen-principal.' . $imageExtension;
            
            $imagePath = $imageFile->storeAs(
                'posts/' . $postSlug, 
                $imageName, 
                'public'
            );
            
            $validated['image_path'] = $imagePath;
        }
    
        // Manejar subida de archivo
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            
            $cleanFileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $fileExtension;
            
            $filePath = $file->storeAs(
                'posts/' . $postSlug, 
                $cleanFileName, 
                'public'
            );
            
            $validated['file_path'] = $filePath;
        }

        // Crear el post
        $publishedAt = null;
        if ($validated['status'] === 'published') {
            $publishedAt = Carbon::now('UTC')->format('Y-m-d H:i:s');
        }
        
        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? null,
            'slug' => $validated['slug'],
            'meta_description' => $validated['meta_description'] ?? null,
            'status' => $validated['status'],
            'is_premium' => $validated['is_premium'] ?? false,
            'image_path' => $validated['image_path'] ?? null,
            'file_path' => $validated['file_path'] ?? null,
            'author_id' => auth()->id(),
            'published_at' => $publishedAt
        ]);
        
        // Asociar tag categories
        if (!empty($validated['tag_categories'])) {
            $post->tagCategories()->attach($validated['tag_categories']);
        }
        
        return redirect()->route('posts.index')
            ->with('success', 'Post creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        // Debug temporal
        Log::info("update");
        Log::info('=== UPDATE METHOD CALLED ===', [
            'method' => $request->method(),
            'url' => $request->url(),
            'content_type' => $request->header('Content-Type'),
            'has_files' => [
                'featured_image' => $request->hasFile('featured_image'),
                'file' => $request->hasFile('file')
            ],
            'all_data' => $request->all()
        ]);
        
        $validated = $request->validated();
        
        
        // Manejar imagen destacada
        if ($request->hasFile('featured_image')) {
            // Si se envía nueva imagen, eliminar la anterior y guardar la nueva
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $validated['image_path'] = $request->file('featured_image')->store('posts/images', 'public');
        } else {
            // Si NO se envía imagen, eliminar la existente y actualizar campo a null
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
                $validated['image_path'] = null;
            }
        }
    
        // Manejar archivo adjunto
        if ($request->hasFile('file')) {
            // Si se envía nuevo archivo, eliminar el anterior y guardar el nuevo
            if ($post->file_path) {
                Storage::disk('public')->delete($post->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('posts/files', 'public');
        } else {
            // Si NO se envía archivo, eliminar el existente y actualizar campo a null
            if ($post->file_path) {
                Storage::disk('public')->delete($post->file_path);
                $validated['file_path'] = null;
            }
        }

        // Determinar el valor de published_at
        $publishedAt = null;
        if ($validated['status'] === 'published') {
            if (isset($validated['published_at'])) {
                $publishedAt = Carbon::parse($validated['published_at'])->format('Y-m-d H:i:s');
            } elseif ($post->published_at) {
                $publishedAt = $post->published_at;
            } else {
                $publishedAt = now()->format('Y-m-d H:i:s');
            }
        }

        // Actualizar el post
        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? $post->excerpt,
            'slug' => $validated['slug'],
            'meta_description' => $validated['meta_description'],
            'status' => $validated['status'],
            'is_premium' => $validated['is_premium'] ?? false,
            'image_path' => $validated['image_path'],
            'file_path' => $validated['file_path'],
            'published_at' => $publishedAt
        ];
        
        
        $post->update($updateData);
    
        // Sincronizar tag categories
        if (isset($validated['tag_categories'])) {
            $post->tagCategories()->sync($validated['tag_categories']);
        }
    
        return redirect()->route('posts.index')
            ->with('success', 'Post actualizado exitosamente.');
    }

    /**
     * Display the specified resource for clients.
     */
    public function clientShow(Post $post)
    {
        // Solo mostrar posts publicados
        if ($post->status !== 'published') {
            abort(404);
        }
    
        // Cargar los tags del post
        $post->load('tags');
    
        return Inertia::render('ClientMenu/Posts/Show', [
            'post' => array_merge($post->toArray(), [
                'tags' => $post->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color
                    ];
                }),
                'image_path' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'file_path' => $post->file_path ? asset('storage/' . $post->file_path) : null,
            ])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load('tags');
        
        return Inertia::render('administration/Posts/Show', [
            'post' => $post
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        $post->load('tags');
        $tags = TagCategory::active()->orderBy('name')->get();
        
        return Inertia::render('administration/Posts/Edit', [
            'post' => array_merge($post->toArray(), [
                'tag_categories' => $post->tags
            ]),
            'availableTags' => $tags
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Eliminar archivos asociados y carpetas
        // Usar el slug almacenado en la base de datos o reconstruirlo desde las rutas de archivos
        $postDirectory = null;
        
        // Intentar obtener la carpeta desde las rutas de archivos existentes
        if ($post->image_path) {
            $postDirectory = dirname($post->image_path);
        } elseif ($post->file_path) {
            $postDirectory = dirname($post->file_path);
        }
        
        // Eliminar archivos individuales primero (si existen)
        if ($post->image_path && Storage::disk('public')->exists($post->image_path)) {
            Storage::disk('public')->delete($post->image_path);
        }
        
        if ($post->file_path && Storage::disk('public')->exists($post->file_path)) {
            Storage::disk('public')->delete($post->file_path);
        }
        
        // Eliminar toda la carpeta del post si se pudo determinar
        if ($postDirectory && Storage::disk('public')->exists($postDirectory)) {
            Storage::disk('public')->deleteDirectory($postDirectory);
        }
    
        // Eliminar las relaciones many-to-many con tags
        $post->tags()->detach();
        
        // Eliminar el post de la base de datos
        $post->delete();
    
        return redirect()->route('posts.index')
            ->with('success', 'Publicación y archivos asociados eliminados exitosamente.');
    }

    /**
     * Cambiar estado de la publicación
     */
    public function changeStatus(Request $request, Post $post)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published,Delete'
        ]);

        // Manejar published_at según el nuevo estado
        $updateData = [
            'status' => $validated['status']
        ];
        
        // Si se está publicando y no tiene fecha de publicación, establecerla
        if ($validated['status'] === 'published' && !$post->published_at) {
            $updateData['published_at'] = now();
        }
        
        // Si se está despublicando (cambiando a draft), mantener la fecha original
        if ($validated['status'] === 'draft') {
            // No modificamos published_at para mantener el historial
        }

        $post->update($updateData);

        return back()->with('success', 'Estado de la publicación actualizado.');
    }

    /**
     * Display published posts for clients
     */
    public function Index()
    {
        $posts = Post::with('tags')
                    ->where('status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($post) {
                        return [
                            'id' => $post->id,
                            'title' => $post->title,
                            'content' => $post->content,
                            'excerpt' => $post->excerpt,
                            'slug' => $post->slug,
                            'tags' => $post->tags->map(function ($tag) {
                                return [
                                    'id' => $tag->id,
                                    'name' => $tag->name,
                                    'slug' => $tag->slug,
                                    'color' => $tag->color
                                ];
                            }),
                            'status' => $post->status,
                            'is_premium' => $post->is_premium,
                            'image_path' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                            'file_path' => $post->file_path ? asset('storage/' . $post->file_path) : null,
                            'created_at' => $post->created_at->format('Y-m-d'),
                            'updated_at' => $post->updated_at->format('Y-m-d')
                        ];
                    });

        return Inertia::render('ClientMenu/Post', [
            'posts' => $posts
        ]);
    }

    /**
     * Eliminar imagen destacada del post
     */
    public function removeImage(Post $post)
    {
        if ($post->image_path && Storage::disk('public')->exists($post->image_path)) {
            Storage::disk('public')->delete($post->image_path);
        }
        
        $post->update(['image_path' => null]);
        
        return back()->with('success', 'Imagen eliminada exitosamente.');
    }
    
    /**
     * Eliminar archivo adjunto del post
     */
    public function removeFile(Post $post)
    {
        if ($post->file_path && Storage::disk('public')->exists($post->file_path)) {
            Storage::disk('public')->delete($post->file_path);
        }
        
        $post->update(['file_path' => null]);
        
        return back()->with('success', 'Archivo eliminado exitosamente.');
    }

/**
 * Método específico para actualización con archivos via POST
 */
public function updateWithFiles(UpdatePostRequest $request, Post $post)
{
    // Usar la misma lógica que update()
    return $this->update($request, $post);
}




}
