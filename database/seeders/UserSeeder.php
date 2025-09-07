<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla antes de insertar
        DB::table('users')->truncate();

        // Crear usuario administrador usando DB::table directamente
        DB::table('users')->insert([
            'name' => 'Administrador',
            'email' => 'admin@admin.cl',
            'password' => Hash::make('123456789'),
            'email_verified_at' => null, // Usuario no verificado inicialmente
            'created_at' => DB::raw('NOW()'),
            'updated_at' => DB::raw('NOW()'),
        ]);

        $this->command->info('Usuario administrador creado correctamente.');
        $this->command->info('Email: admin@admin.cl');
        $this->command->info('Contraseña: 123456789');
    }
}