<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(3),
            'issuer_name' => $this->faker->company(),
            'issued_at' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+5 years'),
            'is_verified' => $this->faker->boolean(30),
        ];
    }
}
