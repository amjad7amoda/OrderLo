<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
        'location',
        'avatar'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public static array $roles = [
        'driver',
        'user',
        'administrator'
    ];

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryOrders() : HasMany {
        return $this->hasMany(Order::class, 'driver_id');
    }

}
