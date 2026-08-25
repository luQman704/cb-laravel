<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name',
        'rate',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'rate'   => 'decimal:3',
            'active' => 'boolean',
        ];
    }
}
