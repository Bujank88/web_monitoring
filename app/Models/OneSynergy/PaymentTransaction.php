<?php

namespace App\Models\OneSynergy;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions_one_synergy';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'transaction_id', 'user_id', 'channel_code', 'customer_phone',
        'customer_email', 'customer_name', 'referral_code', 'transaction_amount',
        'tax_amount', 'grand_total_amount', 'product_category', 'product_type',
        'product_detail', 'status', 'payment_code', 'qris_url', 'redirect_url',
        'transaction_date', 'transaction_expire', 'payment_date',
        'success_email_sent_at', 'gateway_response', 'callback_payload',
        'id_transaksi_myads',
    ];

    protected $casts = [
        'transaction_amount' => 'integer',
        'tax_amount' => 'integer',
        'grand_total_amount' => 'integer',
        'transaction_date' => 'datetime',
        'transaction_expire' => 'datetime',
        'payment_date' => 'datetime',
        'success_email_sent_at' => 'datetime',
    ];
}
