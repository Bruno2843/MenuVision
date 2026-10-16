<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Model3D;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model3D>
 */
class Model3DFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_id' => Dish::factory(),
            'archivo' => 'models/'.fake()->slug().'.glb',
        ];
    }
}
