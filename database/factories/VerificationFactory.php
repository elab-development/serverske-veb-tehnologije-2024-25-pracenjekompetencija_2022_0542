<?php

namespace Database\Factories;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Verification>
 */
class VerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credential_id' => Credential::factory(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn() => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn() => ['status' => 'rejected']);
    }
}
