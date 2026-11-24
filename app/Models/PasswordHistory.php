<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['password'];

    protected $hidden = ['password'];
}
