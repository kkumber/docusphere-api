<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Status;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            StatusSeeder::class,
            User::factory()->count(10)->has(Document::factory()->count(5))->create(),
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
