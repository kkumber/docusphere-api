<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocResponseFile>
 */
class DocResponseFileFactory extends Factory
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
            'uploaded_by' => function (array $attributes) {
                return Document::find($attributes['document_id'])->user_id;
            },
            'file_name' => fake()->lexify('file_?????.pdf'),
            'file_path' => fake()->filePath(),
            'mime_type' => fake()->mimeType(),
            'file_size' => fake()->numberBetween(10000, 10000000),
            'remarks' => fake()->sentence(),
        ];
    }
}
