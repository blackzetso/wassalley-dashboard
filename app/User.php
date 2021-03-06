<?php

namespace App;

use App\Model\CustomerAddress;
use App\Model\Order;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','f_name', 'l_name', 'phone', 'email', 'password','point'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
      'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_phone_verified' => 'integer',
        'point' => 'integer',
    ];

      /**
     * Set the user's first name.
     *
     * @param  string  $value
     * @return void
     */
    public function setPhoneAttribute($value)
    {
       $phone = str_replace('+20', '', $value);
      
      $this->attributes['phone'] = str_replace('+', '', $phone);
    }

    public function orders(){
        return $this->hasMany(Order::class,'user_id');
    }

    public function addresses(){
        return $this->hasMany(CustomerAddress::class,'user_id');
    }
}
