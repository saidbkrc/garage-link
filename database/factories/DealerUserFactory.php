<?php

namespace Database\Factories;

use App\Models\Dealer;
use App\Models\DealerUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DealerUser>
 */
class DealerUserFactory extends Factory
{
    protected $model = DealerUser::class;

    public function definition(): array
    {
        return [
            'dealer_id' => Dealer::factory(),
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => Hash::make('password'),
            'role'      => 'staff',
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
