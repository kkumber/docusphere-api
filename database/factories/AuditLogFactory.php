<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
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
            'user_id' => User::inRandomOrder()->value('id'),
            'activity' => fake()->randomElement(['SIGNED', 'ROUTED', 'RETURNED', 'APPROVED', 'REJECTED', 'COMPLETED', 'ARCHIVED', 'RELEASED']),
        ];
    }
}
