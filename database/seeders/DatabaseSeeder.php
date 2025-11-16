<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            StatusSeeder::class,
        ]);

        User::factory()
            ->count(10)
            ->has(Document::factory()->count(5))
            ->create();

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
