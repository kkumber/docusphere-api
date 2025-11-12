<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Document;
use App\Models\User;
use App\Models\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentTracking>
 */
class DocumentTrackingFactory extends Factory
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
            'from_user' => User::inRandomOrder()->value('id'),
            'to_user' => User::inRandomOrder()->value('id'),
            'status_id' => Status::where('module', 'document_tracking')->InRandomOrder()->value('id'),
            'remarks' => fake()->sentence(),
        ];
    }
}
