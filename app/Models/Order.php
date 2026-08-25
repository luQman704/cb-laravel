<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'guest_email',
        'ship_firstname',
        'ship_lastname',
        'ship_company',
        'ship_address1',
        'ship_address2',
        'ship_city',
        'ship_postcode',
        'ship_country',
        'ship_phone',
        'shipping_method_id',
        'shipping_method_name',
        'status',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'total',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'        => 'string',
            'subtotal'      => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
