<?php

namespace App\Models;

use App\Enums\Role;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === Role::Administrador;
    }

    public function isWaiter(): bool
    {
        return $this->role === Role::Mozo;
    }

    public function isKitchen(): bool
    {
        return $this->role === Role::Cocina;
    }

    /**
     * Whether this account may sign in.
     *
     * A deactivated account keeps its orders and its history; it simply can no
     * longer authenticate, which is what separates "left the company" from
     * "was deleted by mistake".
     */
    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    /**
     * The administrators that can still sign in.
     *
     * The admin panel uses this to refuse to deactivate or demote the last one,
     * which would leave the installation with nobody able to bring it back.
     *
     * @param  Builder<User>  $query
     */
    public function scopeAdministradoresActivos(Builder $query): void
    {
        $query->where('role', Role::Administrador)->where('activo', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'activo' => 'boolean',
        ];
    }
}
