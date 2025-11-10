<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Document;
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
        $statuses = Status::factory()->count(3)->create();


        User::factory()->count(10)->has(Document::factory()->count(5)->state(['status_id' => $statuses->random()->id]))->create();
    }
}
