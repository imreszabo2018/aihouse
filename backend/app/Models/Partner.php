<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'tax_number',
        'country_code',
        'email',
        'address',
    ];
}
