<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'dish_id' => Dish::factory(),
            'cantidad' => fake()->numberBetween(1, 5),
            'precio_unitario' => fake()->randomFloat(2, 5, 40),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}