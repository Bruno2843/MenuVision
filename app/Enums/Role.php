<?php

namespace App\Enums;

enum Role: string
{
    case Administrador = 'administrador';
    case Mozo = 'mozo';
    case Cocina = 'cocina';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Administrador->value => 'Administrador',
            self::Mozo->value => 'Mozo',
            self::Cocina->value => 'Cocina',
        ];
    }

    /**
     * The dashboard a user with this role is redirected to after login.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Administrador => 'admin.dashboard',
            self::Mozo => 'waiter.dashboard',
            self::Cocina => 'kitchen.dashboard',
        };
    }

    /**
     * The human readable name of this role.
     */
    public function label(): string
    {
        return self::options()[$this->value];
    }

    /**
     * The staff panel navigation links available to this role.
     *
     * Each entry is [route name, label, icon name]; the icon is rendered by
     * `partials.icon` so this enum carries no markup.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public function navigation(): array
    {
        return match ($this) {
            self::Administrador => [
                ['admin.dashboard', 'Panel', 'panel'],
                ['admin.categories.index', 'Categorías', 'categorias'],
                ['admin.dishes.index', 'Platos', 'platos'],
                ['admin.ingredients.index', 'Ingredientes', 'ingredientes'],
                ['admin.tables.index', 'Mesas y QR', 'mesas'],
                ['admin.orders.index', 'Pedidos', 'pedidos'],
                ['admin.users.index', 'Usuarios', 'usuarios'],
            ],
            self::Mozo => [
                ['waiter.dashboard', 'Pedidos', 'pedidos'],
                ['waiter.menu', 'Carta', 'platos'],
                ['waiter.orders.create', 'Tomar pedido', 'mas'],
            ],
            self::Cocina => [
                ['kitchen.dashboard', 'Cocina', 'platos'],
            ],
        };
    }
}
