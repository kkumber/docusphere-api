<?php

namespace Database\Factories;

use Database\Seeders\StatusSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracking_no' => fake()->unique()->uuid(),
            'title' => fake()->word(),
            'instructions' => fake()->sentence(),
            'category' => fake()->randomElement(['Memorandum', 'Unnumbered Memorandum', 'Advisory', 'Endorsement']),
            'originating_office' => fake()->company(),
            'request_type'=> fake()->randomElement(['For Signature','For Approval','For Information', 'For Review', 'For Action']),
            'user_id' => null,
            'status_id' => Status::where('module', 'document')->InRandomOrder()->value('id'),
            'due_date' => fake()->date(),
        ];
    }
}
