<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentFile>
 */
class DocumentFileFactory extends Factory
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
            'file_name' => fake()->lexify('file_?????.pdf'),
            'public_id' => fake()->filePath(),
            'mime_type' => fake()->mimeType(),
            'file_size' => fake()->numberBetween(10000, 10000000),
            'uploaded_by' => function (array $attributes) {
                return Document::find($attributes['document_id'])->uploaded_by;
            },
            'is_primary' => fake()->boolean(),
        ];
    }
}
