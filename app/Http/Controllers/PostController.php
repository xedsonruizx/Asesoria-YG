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
  public function index(Request $request)
    {
        $query = Post::with('tags')
            ->where('status', 'published')
            ->where('is_active', true);

        // Filtrar por tags si se proporciona
        if ($request->filled('tag_id')) {
            $query->withTags([$request->tag_id]);
        }

        // Búsqueda por título
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $posts = $query->orderBy('published_at', 'desc')->paginate(12);
        $tags = TagCategory::active()->orderBy('name')->get();

        return Inertia::render('ClientMenu/Post', [
            'posts' => $posts,
            'tags' => $tags,
            'filters' => $request->only(['tag_id', 'search'])
        ]);
    }








    public function AdminIndex(Request $request)
    {
        $query = Post::with('tags');

        // Filtro para mostrar eliminados
        if ($request->filled('show_deleted')) {
            if ($request->show_deleted === 'only') {
                $query->onlyTrashed();
            } elseif ($request->show_deleted === 'with') {
                $query->withTrashed();
            }
        }

        // Filtro por estado activo/inactivo
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

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
            'filters' => $request->only(['tag_id', 'status', 'search', 'show_deleted', 'is_active'])
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
        
        // Establecer is_active = true por defecto si no se proporciona
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }
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
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Post $post)
    {
        try {
            // Eliminar archivos asociados y carpetas
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
        
            // Establecer campos de archivo como null antes del soft delete
            $post->update([
                'image_path' => null,
                'file_path' => null
            ]);
            
            // Eliminar las relaciones many-to-many con tags
            $post->tags()->detach();
            
            // Soft delete del post
            $post->delete();
        
            return redirect()->route('posts.admin')
                           ->with('success', 'Post eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar post: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar el post.']);
        }
    }

    /**
     * Restore a soft deleted post
     */
    public function restore($id)
    {
        try {
            $post = Post::onlyTrashed()->findOrFail($id);
            $post->restore();

            return redirect()->route('posts.admin.index')
                           ->with('success', 'Post restaurado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al restaurar post: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al restaurar el post.']);
        }
    }

    /**
     * Permanently delete a post (force delete)
     */
    public function forceDelete($id)
    {
        try {
            $post = Post::onlyTrashed()->findOrFail($id);
            $post->forceDelete();

            return redirect()->route('posts.admin.index')
                           ->with('success', 'Post eliminado permanentemente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar permanentemente post: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al eliminar permanentemente el post.']);
        }
    }

    /**
     * Toggle status of post
     */
    public function toggleStatus(Post $post)
    {
        try {
            $post->update([
                'is_active' => !$post->is_active
            ]);

            $status = $post->is_active ? 'activado' : 'desactivado';
            return redirect()->route('posts.admin.index')
                           ->with('success', "Post {$status} exitosamente.");
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de post: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al cambiar el estado del post.']);
        }
    }
}
