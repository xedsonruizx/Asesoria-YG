<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\TagCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;



//TODO: Revisar guardado de archio, excerpt, meta_description, tags, category_id que no se estan guardando
class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'meta_description' => 'nullable|string|max:160',
            'status' => 'required|in:draft,published',
            'is_premium' => 'boolean',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|file|max:10240',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags_category,id',
            'category_id' => 'nullable|exists:categories,id'
        ]);
        
        // Generar slug si no se proporciona
        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);
        }
        
        // Crear un slug único para la carpeta basado en el título
        $postSlug = \Illuminate\Support\Str::slug($validated['title']) . '-' . time();
        
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
            
            $cleanFileName = \Illuminate\Support\Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $fileExtension;
            
            $filePath = $file->storeAs(
                'posts/' . $postSlug, 
                $cleanFileName, 
                'public'
            );
            
            $validated['file_path'] = $filePath;
        }
    
        // Verificar que hay un usuario autenticado
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes estar autenticado para crear un post.');
        }

        // Crear el post
        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? null,
            'slug' => $validated['slug'],
            'meta_description' => $validated['meta_description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'],
            'is_premium' => $validated['is_premium'] ?? false,
            'image_path' => $validated['image_path'] ?? null,
            'file_path' => $validated['file_path'] ?? null,
            'author_id' => auth()->id(),
            'published_at' => $validated['status'] === 'published' ? now() : null
        ]);
        
        // Asociar tags
        if (!empty($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }
    
        return redirect()->route('posts.index')
            ->with('success', 'Publicación creada exitosamente.');
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

        return Inertia::render('ClientMenu/Posts/Show', [
            'post' => $post
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
            'post' => $post,
            'tags' => $tags
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:60000',
            'excerpt' => 'nullable|string|max:160',
            'slug' => 'required|string|max:255|unique:posts,slug,' . $post->id,
            'meta_description' => 'nullable|string|max:160',
            'status' => 'required|in:draft,published',
            'is_premium' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|file|max:10240',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags_category,id'
        ]);

        // Manejar nueva imagen
        if ($request->hasFile('image')) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('posts/images', 'public');
        }

        // Manejar nuevo archivo
        if ($request->hasFile('file')) {
            if ($post->file_path) {
                Storage::disk('public')->delete($post->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('posts/files', 'public');
        }

        // Actualizar el post
        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? null,
            'slug' => $validated['slug'],
            'meta_description' => $validated['meta_description'] ?? null,
            'status' => $validated['status'],
            'is_premium' => $validated['is_premium'] ?? false,
            'image_path' => $validated['image_path'] ?? $post->image_path,
            'file_path' => $validated['file_path'] ?? $post->file_path,
            'published_at' => $validated['status'] === 'published' && !$post->published_at ? now() : $post->published_at
        ]);
        
        // Sincronizar tags
        $post->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('posts.index')
            ->with('success', 'Publicación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Eliminar archivos asociados
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }
        if ($post->file_path) {
            Storage::disk('public')->delete($post->file_path);
        }

        $post->delete();

        return redirect()->route('posts.index')
            ->with('success', 'Publicación eliminada exitosamente.');
    }

    /**
     * Cambiar estado de la publicación
     */
    public function changeStatus(Request $request, Post $post)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,published,Delete'
        ]);

        $post->update($validated);

        return back()->with('success', 'Estado de la publicación actualizado.');
    }

    /**
     * Display published posts for clients
     */
    public function AdminIndex()
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
}