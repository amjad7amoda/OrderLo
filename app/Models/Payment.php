<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{

    protected $fillable = [
        'user_id',
        'payment_method',
        'card_number'
    ];

    public static array $payment_methods = [
        'MTN-Cash',
        'Syriatel-Cash',
        'BBFS',
        'Barka'
    ];

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }
}
