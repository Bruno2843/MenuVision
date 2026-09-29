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
            ['nombre' => 'Entradas', 'descripcion' => 'Para empezar a compartir', 'orden' => 1],
            ['nombre' => 'Hamburguesas', 'descripcion' => 'Hamburguesas artesanales', 'orden' => 2],
            ['nombre' => 'Pizzas', 'descripcion' => 'Pizzas al horno de piedra', 'orden' => 3],
            ['nombre' => 'Pastas', 'descripcion' => 'Pastas frescas', 'orden' => 4],
            ['nombre' => 'Postres', 'descripcion' => 'Postres caseros', 'orden' => 5],
            ['nombre' => 'Bebidas', 'descripcion' => 'Bebidas frías y calientes', 'orden' => 6],
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
            ['category' => 'Entradas', 'nombre' => 'Papas fritas', 'descripcion' => 'Con queso y mayonesa de la casa', 'precio' => 6.00, 'orden' => 1, 'ingredientes' => ['Harina']],
            ['category' => 'Entradas', 'nombre' => 'Empanadas de carne', 'descripcion' => 'Seis unidades, masa casera', 'precio' => 8.50, 'orden' => 2, 'ingredientes' => ['Harina', 'Carne']],
            ['category' => 'Entradas', 'nombre' => 'Ensalada caprese', 'descripcion' => 'Palmito, tomate y huevo', 'precio' => 7.00, 'orden' => 3, 'ingredientes' => ['Lechuga', 'Tomate']],
            ['category' => 'Hamburguesas', 'nombre' => 'Hamburguesa Clásica', 'descripcion' => 'Carne 150g, queso y tus favoritos', 'precio' => 12.50, 'orden' => 1, 'ingredientes' => ['Pan', 'Carne', 'Queso', 'Lechuga', 'Tomate']],
            ['category' => 'Hamburguesas', 'nombre' => 'Hamburguesa BBQ', 'descripcion' => 'Salsa BBQ y queso cheddar', 'precio' => 14.00, 'orden' => 2, 'ingredientes' => ['Pan', 'Carne', 'Queso', 'Salsa']],
            ['category' => 'Pizzas', 'nombre' => 'Pizza Margarita', 'descripcion' => 'Tomate, muzzarella y albahaca', 'precio' => 16.00, 'orden' => 1, 'ingredientes' => ['Harina', 'Tomate', 'Queso']],
            ['category' => 'Pizzas', 'nombre' => 'Pizza pepperoni', 'descripcion' => 'Pepperoni y mozzarella', 'precio' => 18.00, 'orden' => 2, 'ingredientes' => ['Harina', 'Tomate', 'Queso']],
            ['category' => 'Pastas', 'nombre' => 'Fetuccini Alfredo', 'descripcion' => 'Crema, queso parmesano y perejil', 'precio' => 18.00, 'orden' => 1, 'ingredientes' => ['Harina', 'Queso']],
            ['category' => 'Pastas', 'nombre' => 'Tallarines a la boloñesa', 'descripcion' => 'Salsa de carne y tomate', 'precio' => 16.50, 'orden' => 2, 'ingredientes' => ['Harina', 'Salsa', 'Carne']],
            ['category' => 'Postres', 'nombre' => 'Tiramisú', 'descripcion' => 'Mascarpone y cacao', 'precio' => 9.00, 'orden' => 1, 'ingredientes' => []],
            ['category' => 'Postres', 'nombre' => 'Flan casero', 'descripcion' => 'Con dulce de leche', 'precio' => 7.50, 'orden' => 2, 'ingredientes' => []],
            ['category' => 'Bebidas', 'nombre' => 'Limonada', 'descripcion' => 'Con menta y hierbabuena', 'precio' => 4.50, 'orden' => 1, 'ingredientes' => []],
            ['category' => 'Bebidas', 'nombre' => 'Café espresso', 'descripcion' => 'Grano Brazil', 'precio' => 3.00, 'orden' => 2, 'ingredientes' => []],
        ];

        foreach ($dishes as $data) {
            $dish = Dish::updateOrCreate(
                ['nombre' => $data['nombre']],
                [
                    'category_id' => $categories->get($data['category'])->id,
                    'descripcion' => $data['descripcion'],
                    'precio' => $data['precio'],
                    'orden' => $data['orden'],
                    'disponible' => true,
                ]
            );

            $dish->ingredients()->sync(
                collect($data['ingredientes'])->map(fn (string $name): int => $ingredients->get($name)->id)
            );
        }
    }
}
