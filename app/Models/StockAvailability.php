<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAvailability extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'allow_out_of_stock',
    ];

    protected function casts(): array
    {
        return [
            'allow_out_of_stock' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
