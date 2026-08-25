<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'delay',
        'base_price',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'active'     => 'boolean',
        ];
    }
}
