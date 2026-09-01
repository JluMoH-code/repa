<?php

namespace Database\Factories;

use App\Enums\OrderDeliveryMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(10000, 200000);

        return [
            'number' => 'Р-'.$this->faker->unique()->numberBetween(2020, 2030).'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => '+7'.str_pad((string) $this->faker->unique()->numberBetween(1000000000, 9999999999), 10, '0', STR_PAD_LEFT),
            'delivery_method' => OrderDeliveryMethod::Pickup,
            'delivery_city' => null,
            'delivery_postcode' => null,
            'delivery_address' => null,
            'comment' => $this->faker->optional(0.3)->sentence(),
            'status' => OrderStatus::New,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'placed_at' => now(),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'customer_email' => $this->faker->unique()->safeEmail(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
        ]);
    }
}
