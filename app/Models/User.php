<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_verified_at',
        'farmer_verified_at',
        'password',
        'role',
        'latitude',
        'longitude',
        'location_name',
        'profile_photo_path',
        'show_on_map',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'farmer_verified_at' => 'datetime',
            'password' => 'hashed',
            'show_on_map' => 'boolean',
        ];
    }

    public function products() { return $this->hasMany(Product::class, 'farmer_id'); }
    public function orders() { return $this->hasMany(Order::class, 'buyer_id'); }
    public function farmOperationLogs() { return $this->hasMany(FarmOperationLog::class, 'farmer_id'); }
}
