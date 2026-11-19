<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'tax_number',
        'country_code',
        'email',
        'address',
    ];

    public function sellerInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'seller_id');
    }

    public function buyerInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'buyer_id');
    }
}