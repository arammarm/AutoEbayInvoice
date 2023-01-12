<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_status',
        'total',
        'ordered_date',
        'buyer',
        'order_detail',
        'invoice_details',
        'ref',
        'country',
        'last_downloaded',
        'purchase_history'
    ];

    protected $casts = [];
}
