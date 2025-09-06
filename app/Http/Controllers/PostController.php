<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Post::query();

        // Filtrar por categoría si se proporciona
        if ($request->filled('category')) {
            $query->byCategory($request->category);
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

        return Inertia::render('administration/Post', [
            'posts' => $posts,
            'filters' => $request->only(['category', 'status', 'search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('administration/Post');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:60000',
            'Category' => 'required|string|max:255',
            'status' => 'required|in:draft,published,Delete',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|file|max:10240', // 10MB max
            'Subscripcion' => 'boolean'
        ]);

        // Manejar subida de imagen
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('posts/images', 'public');
        }

        // Manejar subida de archivo
        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('posts/files', 'public');
        }

        Post::create($validated);

        return redirect()->route('posts.index')
            ->with('success', 'Publicación creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return Inertia::render('administration/Posts/Show', [
            'post' => $post
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        return Inertia::render('Posts/Edit', [
            'post' => $post
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
            'Category' => 'required|string|max:255',
            'status' => 'required|in:draft,published,Delete',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'file' => 'nullable|file|max:10240',
            'Subscripcion' => 'boolean'
        ]);

        // Manejar nueva imagen
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('posts/images', 'public');
        }

        // Manejar nuevo archivo
        if ($request->hasFile('file')) {
            // Eliminar archivo anterior si existe
            if ($post->file_path) {
                Storage::disk('public')->delete($post->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('posts/files', 'public');
        }

        $post->update($validated);

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
}