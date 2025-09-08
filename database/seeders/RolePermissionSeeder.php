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
            'manage',
            'guest',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $clienteRole = Role::firstOrCreate(['name' => 'cliente']);

        // Asignar permisos a roles
        $adminRole->givePermissionTo(['manage', 'guest']);
        $clienteRole->givePermissionTo(['guest']);

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