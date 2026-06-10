<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransactionDetail extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payment_transaction_id',
        'payment_id',
        'quota_id',
        'quota_balance_before',
        'amount_applied',
        'quota_balance_after',
        'sequence',
    ];

    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function quota()
    {
        return $this->belongsTo(Quota::class);
    }
}
