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
    case SinInsumos = 'sin_insumos';

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
            self::SinInsumos->value => 'Sin insumos',
        ];
    }

    /**
     * The happy path an order walks from the waiter to the table, in order.
     * Used to render the progress indicator the waiter watches.
     *
     * @return array<int, self>
     */
    public static function flow(): array
    {
        return [
            self::Pendiente,
            self::Confirmado,
            self::EnPreparacion,
            self::Listo,
            self::Entregado,
        ];
    }

    /**
     * The statuses an order is still being worked on. An order in any other
     * status no longer keeps its table occupied.
     *
     * @return array<int, string>
     */
    public static function open(): array
    {
        return [
            self::Pendiente->value,
            self::Confirmado->value,
            self::EnPreparacion->value,
            self::Listo->value,
            self::SinInsumos->value,
        ];
    }

    /**
     * The statuses the waiter may move this order to, given where it is now.
     * The waiter owns the first step of the flow and the rejection of a
     * handback, but a handback can only travel back to the kitchen through
     * the resend form, where the dishes can still be changed.
     *
     * @return array<int, string>
     */
    public function waiterTransitions(): array
    {
        return match ($this) {
            self::Pendiente => [
                self::Confirmado->value,
                self::Rechazado->value,
            ],
            self::SinInsumos => [self::Rechazado->value],
            default => [],
        };
    }

    /**
     * The statuses the kitchen may move this order to: the next step of the
     * flow, plus handing it back when an ingredient is missing.
     *
     * @return array<int, string>
     */
    public function kitchenTransitions(): array
    {
        return array_values(array_filter([
            $this->nextKitchenStatus()?->value,
            $this->canBeReturned() ? self::SinInsumos->value : null,
        ]));
    }

    /**
     * The status the kitchen moves this order to, or null when the order is
     * not in the part of the flow the kitchen owns.
     */
    public function nextKitchenStatus(): ?self
    {
        return match ($this) {
            self::Confirmado => self::EnPreparacion,
            self::EnPreparacion => self::Listo,
            self::Listo => self::Entregado,
            default => null,
        };
    }

    /**
     * Whether the kitchen may hand this order back to the waiter because an
     * ingredient is missing. Only while the food is still being produced.
     */
    public function canBeReturned(): bool
    {
        return in_array($this, [self::Confirmado, self::EnPreparacion], true);
    }

    /**
     * The label of the button that advances this order to the next kitchen
     * status, or null when the kitchen has nothing left to do with it.
     */
    public function kitchenAction(): ?string
    {
        return match ($this) {
            self::Confirmado => 'Empezar preparación',
            self::EnPreparacion => 'Marcar como listo',
            self::Listo => 'Marcar como entregado',
            default => null,
        };
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
            self::Pendiente => 'bg-gray-800 text-gray-300',
            self::Confirmado, self::EnPreparacion => 'bg-amber-950 text-amber-300',
            self::Listo => 'bg-green-950 text-green-300',
            self::Entregado => 'bg-sky-950 text-sky-300',
            self::Rechazado => 'bg-red-950 text-red-300',
            self::SinInsumos => 'bg-orange-950 text-orange-300',
        };
    }
}
