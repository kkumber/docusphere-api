<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::inRandomOrder()->value('id'),
            'uploaded_by' => User::inRandomOder()->value('id'),
            'version_number' => 1,
            'previous_version_id' => null,
            'modification_type' => fake()->randomElement(['SIGNED', 'UPDATED']),
            'file_name' => fake()->lexify('file_?????.pdf'),
            'file_path' => fake()->filePath(),
            'mime_type' => fake()->mimeType(),
            'file_size' => fake()->randomNumber(5),
        ];
    }
}
