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
     * @return array<int, array{0: string, 1: string}>
     */
    public function navigation(): array
    {
        return match ($this) {
            self::Administrador => [
                ['admin.dashboard', 'Panel'],
                ['admin.categories.index', 'Categorías'],
                ['admin.dishes.index', 'Platos'],
            ],
            self::Mozo => [
                ['waiter.dashboard', 'Pedidos'],
                ['waiter.menu', 'Carta'],
                ['waiter.orders.create', 'Tomar pedido'],
            ],
            self::Cocina => [
                ['kitchen.dashboard', 'Cocina'],
            ],
        };
    }
}
