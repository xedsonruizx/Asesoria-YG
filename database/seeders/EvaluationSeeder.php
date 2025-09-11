<?php

namespace Database\Seeders;

use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener usuarios
        $adminUser = User::where('email', 'admin@admin.cl')->first();
        $premiumUser = User::where('email', 'premium@test.cl')->first();
        $regularUser = User::where('email', 'user@test.cl')->first();

        if (!$adminUser || !$premiumUser || !$regularUser) {
            $this->command->error('Los usuarios no existen. Ejecuta primero UserSeeder.');
            return;
        }

        $evaluations = [
            [
                'user_id' => $adminUser->id,
                'status' => 'completed',
                'total_score' => 85,
                'total_progress' => 100,
                'completed_at' => DB::raw('NOW()'),
                'is_active' => true,
            ],
            [
                'user_id' => $premiumUser->id,
                'status' => 'in_progress',
                'total_score' => 45,
                'total_progress' => 60,
                'completed_at' => null,
                'is_active' => true,
            ],
            [
                'user_id' => $regularUser->id,
                'status' => 'draft',
                'total_score' => 0,
                'total_progress' => 0,
                'completed_at' => null,
                'is_active' => true,
            ],
            [
                'user_id' => $adminUser->id,
                'status' => 'completed',
                'total_score' => 92,
                'total_progress' => 100,
                'completed_at' => DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'),
                'is_active' => false,
            ],
        ];

        foreach ($evaluations as $evaluation) {
            DB::table('evaluations')->insert(array_merge($evaluation, [
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ]));
        }

        $this->command->info('Evaluaciones creadas correctamente.');
    }
}