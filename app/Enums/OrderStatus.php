<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case Rechazado = 'rechazado';
    case EnPreparacion = 'en_preparacion';
    case Listo = 'listo';
    case Entregado = 'entregado';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Pendiente->value => 'Pendiente',
            self::Confirmado->value => 'Confirmado',
            self::Rechazado->value => 'Rechazado',
            self::EnPreparacion->value => 'En preparación',
            self::Listo->value => 'Listo',
            self::Entregado->value => 'Entregado',
        ];
    }
}
