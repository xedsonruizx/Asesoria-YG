<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Blade;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            'view posts',
            'create posts', 
            'edit posts',
            'delete posts',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);
        $userRole = Role::create(['name' => 'user']);

        // Asignar permisos a roles
        $adminRole->givePermissionTo(Permission::all());
        $editorRole->givePermissionTo(['view posts', 'create posts', 'edit posts']);
        $userRole->givePermissionTo(['view posts']);

        // Asignar rol admin al primer usuario (si existe)
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('admin');
        }
    }
}



// // Verificar permisos
// if ($user->can('edit posts')) {
//     // El usuario puede editar posts
// }

// // Verificar roles
// if ($user->hasRole('admin')) {
//     // El usuario es administrador
// }

// // Asignar roles/permisos
// $user->assignRole('editor');
// $user->givePermissionTo('create posts');


// Blade
// @role('admin')
//     <p>Solo los administradores ven esto</p>
// @endrole

// @can('edit posts')
//     <a href="{{ route('posts.edit', $post) }}">Editar Post</a>
// @endcan