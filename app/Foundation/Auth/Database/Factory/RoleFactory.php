<?php

namespace App\Foundation\Auth\Database\Factory;

use App\Foundation\Auth\Model\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * The name is the only thing a role cannot be given twice, and it is short enough that a
     * sentence would not fit — so it is numbered rather than worded.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => 'Role '.fake()->unique()->numerify('######'),
        ];
    }
}
