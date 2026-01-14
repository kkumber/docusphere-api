<?php

namespace Database\Factories;

use App\Enums\RequestType;
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
            'subject' => fake()->title(),
            'data' => [
                'request_type' => fake()->randomElement(array_column(RequestType::cases(), 'value')),
                'assigned_by' => User::inRandomOrder()->value('id'),
                'instructions' => fake()->sentence(),
            ],
            'is_read' => fake()->boolean(),
        ];
    }
}
