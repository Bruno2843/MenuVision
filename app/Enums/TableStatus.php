<?php

namespace App\Enums;

enum TableStatus: string
{
    case Libre = 'libre';
    case Ocupada = 'ocupada';

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::Libre->value => 'Libre',
            self::Ocupada->value => 'Ocupada',
        ];
    }

    /**
     * The human readable name of this status.
     */
    public function label(): string
    {
        return self::options()[$this->value];
    }

    /**
     * Tailwind classes for the badge that represents this status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Libre => 'bg-green-950 text-green-300',
            self::Ocupada => 'bg-amber-950 text-amber-300',
        };
    }
}
