<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Document;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id'),
            'document_id' => Document::inRandomOrder()->value('id'),
            'message' => fake()->sentence(),
            'type' => fake()->randomElement(['info', 'reminder', 'delay', 'escalation']),
            'is_read' => fake()->boolean(),
        ];
    }
}
