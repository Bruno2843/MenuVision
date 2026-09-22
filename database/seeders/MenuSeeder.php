<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Ingredient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed example categories, ingredients and dishes.
     */
    public function run(): void
    {
        $categories = [
            ['nombre' => 'Hamburguesas', 'descripcion' => 'Hamburguesas artesanales'],
            ['nombre' => 'Pizzas', 'descripcion' => 'Pizzas al horno de piedra'],
            ['nombre' => 'Pastas', 'descripcion' => 'Pastas frescas'],
            ['nombre' => 'Postres', 'descripcion' => 'Postres caseros'],
            ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frías y calientes'],
        ];

        $categories = collect($categories)->mapWithKeys(function (array $data): array {
            $category = Category::updateOrCreate(['nombre' => $data['nombre']], $data);

            return [$category->nombre => $category];
        });

        $ingredients = collect(['Carne', 'Queso', 'Pan', 'Tomate', 'Lechuga', 'Salsa', 'Harina'])
            ->mapWithKeys(function (string $name): array {
                $ingredient = Ingredient::updateOrCreate(['nombre' => $name]);

                return [$name => $ingredient];
            });

        $dishes = [
            ['category' => 'Hamburguesas', 'nombre' => 'Hamburguesa Clásica', 'precio' => 12.50, 'ingredientes' => ['Pan', 'Carne', 'Queso', 'Lechuga', 'Tomate']],
            ['category' => 'Hamburguesas', 'nombre' => 'Hamburguesa BBQ', 'precio' => 14.00, 'ingredientes' => ['Pan', 'Carne', 'Queso', 'Salsa']],
            ['category' => 'Pizzas', 'nombre' => 'Pizza Margarita', 'precio' => 16.00, 'ingredientes' => ['Harina', 'Tomate', 'Queso']],
            ['category' => 'Pastas', 'nombre' => 'Fetuccini Alfredo', 'precio' => 18.00, 'ingredientes' => ['Harina', 'Queso']],
            ['category' => 'Postres', 'nombre' => 'Tiramisú', 'precio' => 9.00, 'ingredientes' => []],
            ['category' => 'Bebidas', 'nombre' => 'Limonada', 'precio' => 4.50, 'ingredientes' => []],
        ];

        foreach ($dishes as $data) {
            $dish = Dish::updateOrCreate(
                ['nombre' => $data['nombre']],
                [
                    'category_id' => $categories->get($data['category'])->id,
                    'descripcion' => 'Plato de prueba',
                    'precio' => $data['precio'],
                    'disponible' => true,
                ]
            );

            $dish->ingredients()->sync(
                collect($data['ingredientes'])->map(fn (string $name): int => $ingredients->get($name)->id)
            );
        }
    }
}