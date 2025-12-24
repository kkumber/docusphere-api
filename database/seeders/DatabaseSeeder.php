<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            StatusSeeder::class,
        ]);

        $roles = Role::all();

        User::factory()
            ->count(10)
            ->has(Document::factory()->count(5))
            ->create()
            ->each(function (User $user) use($roles) {
                $user->assignRole($roles->random());
            });

        $this->call([
            DocumentFileSeeder::class,
            DocResponseFileSeeder::class,
            DocumentAssignmentSeeder::class,
            DocumentTrackingSeeder::class,
            DocumentVersionSeeder::class,
            DocumentCommentSeeder::class,
            AuditLogSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
