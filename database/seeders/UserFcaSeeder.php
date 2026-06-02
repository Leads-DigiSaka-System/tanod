<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserFca;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserFcaSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('fca');

        User::factory()
            ->count(10)
            ->create([
                'is_active' => true,
            ])
            ->each(function (User $user): void {
                $user->assignRole('fca');
                UserFca::factory()->create([
                    'user_id' => $user->id,
                ]);
            });
    }
}
