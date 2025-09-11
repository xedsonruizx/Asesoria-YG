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
        // Usar delete en lugar de truncate para evitar problemas con foreign keys
        DB::table('users')->delete();

        // Crear usuario administrador usando DB::table directamente
        DB::table('users')->insert([
            'name' => 'Administrador',
            'email' => 'admin@admin.cl',
            'password' => Hash::make('123456789'),
            'email_verified_at' => null,
            'created_at' => DB::raw('NOW()'),
            'updated_at' => DB::raw('NOW()'),
            'ispremium' => true,
            'is_active' => true,
        ]);

        // Crear usuarios adicionales
        DB::table('users')->insert([
            [
                'name' => 'Usuario Premium',
                'email' => 'premium@test.cl',
                'password' => Hash::make('123456789'),
                'email_verified_at' => DB::raw('NOW()'),
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'ispremium' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Usuario Regular',
                'email' => 'user@test.cl',
                'password' => Hash::make('123456789'),
                'email_verified_at' => DB::raw('NOW()'),
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'ispremium' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Usuario Inactivo',
                'email' => 'inactive@test.cl',
                'password' => Hash::make('123456789'),
                'email_verified_at' => null,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
                'ispremium' => false,
                'is_active' => false,
            ]
        ]);

        $this->command->info('Usuarios creados correctamente.');
        $this->command->info('Email: admin@admin.cl');
        $this->command->info('Contraseña: 123456789');
    }
}