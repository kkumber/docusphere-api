<?php

namespace Database\Seeders;

use App\Models\DocumentFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentFile::factory()->count(50)->create();
    }
}
