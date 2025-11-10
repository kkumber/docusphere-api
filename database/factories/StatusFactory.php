<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module' => fake()->randomElement(['document', 'document_assignment', 'document_tracking']),
            'code' => fake()->randomElement(['PENDING', 'DELAYED', 'COMPLETED', 'ARCHIVED', 'RELEASED', 'ROUTED', 'RETURNED', 'APPROVED']),
            'label' => fake()->randomElement(['Pending', 'Delayed', 'Completed', 'Archived', 'Released', 'Routed', 'Returned', 'Approved']),
            'is_active' => fake()->boolean(),
        ];
    }
}
