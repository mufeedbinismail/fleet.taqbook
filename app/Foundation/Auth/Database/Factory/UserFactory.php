<?php

namespace App\Foundation\Auth\Database\Factory;

use App\Foundation\Auth\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Only what a user has to be given: the two that must be unique, the name a screen shows, and
     * a password. Every other column of this table carries its own default, and restating those
     * here would be a second place for them to be decided from — one that goes quietly out of step
     * the first time the table is altered.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->userName(),
            'real_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret-secret',
        ];
    }
}
