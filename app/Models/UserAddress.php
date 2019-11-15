<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    //
    protected $table = 'user_addresses';
    protected $attributes = [
        'default_address' => 0,
    ];
}
