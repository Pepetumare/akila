<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',       // <-- añadir
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ← Cambiado de método a propiedad
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean',  // <-- añadir
    ];

    /**
     * Determina si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        // Forzamos a bool, incluso si el atributo es null:
        return (bool) $this->is_admin;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
