<?php

namespace Database\Factories;

use App\Models\AdminCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminCard>
 */
class AdminCardFactory extends Factory
{
    protected $model = AdminCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => fake()->numberBetween(1, 1000),
            'creator_name' => fake()->userName(),
            'creator_server' => fake()->randomElement(['one', 'two', 'three']),
            'target_admin_name' => fake()->userName(),
            'action_type' => fake()->randomElement(['warning_add', 'warning_remove', 'permanent_ban']),
            'reason' => fake()->sentence(10),
            'evidence' => fake()->optional()->url(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Indicate that the card is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    /**
     * Indicate that the card is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => fake()->numberBetween(1, 1000),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the card is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => fake()->numberBetween(1, 1000),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the card requires confirmation.
     */
    public function requiresConfirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'requires_confirmation',
            'action_type' => 'permanent_ban',
            'reviewed_by' => fake()->numberBetween(1, 1000),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the action is a warning add.
     */
    public function warningAdd(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'warning_add',
        ]);
    }

    /**
     * Indicate that the action is a warning remove.
     */
    public function warningRemove(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'warning_remove',
        ]);
    }

    /**
     * Indicate that the action is a permanent ban.
     */
    public function permanentBan(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'permanent_ban',
        ]);
    }
}
