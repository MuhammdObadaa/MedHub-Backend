<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'password',
        'pharmacyName',
        'phoneNumber',
        'image',
        'FCMToken'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    //returns all the carts that the user sent
    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_id', 'id')->latest();
    }
    //returns the medicines that the user favored
    public function favors()
    {
        return $this->belongsToMany(Medicine::class, 'medicine_user', 'user_id', 'medicine_id')->where('available', 1)->withTimestamps();
    }
    //returns if the user has favored a medicine or not
    public function hasFavored(Medicine $medicine)
    {
        return $this->favors()->where('medicine_id', $medicine->id)->exists();
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    //TODO: distribute between user and medicine image

    public function getImageURL()
    {
        if ($this->image != "" && $this->image != null) {
            //first arg is the path of the file relative to the public directory
            return url('storage', $this->image);
        }
        return '';
    }
}
