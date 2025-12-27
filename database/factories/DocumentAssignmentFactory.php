<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\User;
use App\Models\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentAssignment>
 */
class DocumentAssignmentFactory extends Factory
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
            'assigned_to' => User::inRandomOrder()->value('id'),
            'assigned_by' => function (array $attributes) {
                return Document::find($attributes['document_id'])->uploaded_by;
            },
            'status_id' => Status::where('module', 'document_assignment')->InRandomOrder()->value('id'),
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
}
