<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Fictional demo clients only (UAT condition: no real client data).
        return [
            'name' => fake()->unique()->company().' Sdn Bhd',
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'is_active' => true,
        ];
    }
}
