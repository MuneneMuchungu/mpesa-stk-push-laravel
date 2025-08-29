<?php

namespace GrishonMunene\MpesaStk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_request_id',
        'merchant_request_id',
        'phone_number',
        'amount',
        'account_reference',
        'transaction_desc',
        'mpesa_receipt_number',
        'status',
        'callback_response'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
